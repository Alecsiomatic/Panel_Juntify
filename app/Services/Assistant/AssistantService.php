<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversation;
use App\Models\AssistantDocument;
use App\Models\AssistantMessage;
use App\Models\User;
use App\Services\Assistant\JuntifyConversationAdapter;
use App\Services\Calendar\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AssistantService
{
    public function __construct(
        private readonly OpenAiClient $client,
        private readonly AssistantContextBuilder $contextBuilder,
        private readonly GoogleCalendarService $calendarService,
    ) {
    }

    public function ensureConversation(User $user, ?int $conversationId = null): AssistantConversation
    {
        if ($conversationId) {
            $conversation = $user->assistantConversations()->findOrFail($conversationId);
        } else {
            $conversation = $user->assistantConversations()->create([
                'title' => 'Nueva conversación',
            ]);

            $conversation->messages()->create([
                'role' => 'system',
                'content' => $this->buildSystemPrompt(),
            ]);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => 'Hola, soy tu asistente DDU. Puedo apoyarte con tus reuniones, documentos y eventos de calendario usando el contexto que selecciones. ¿En qué puedo ayudarte hoy?',
            ]);
        }

        return $conversation;
    }

    public function registerUserMessage(AssistantConversation $conversation, string $message, array $metadata = []): AssistantMessage
    {
        return $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Generar respuesta del asistente
     * 
     * @param User $user
     * @param AssistantConversation $conversation
     * @param object $settings Configuración del asistente (puede ser objeto con enable_drive_calendar)
     * @param string $message
     * @param array $options
     * @return AssistantMessage
     */
    public function generateAssistantReply(User $user, AssistantConversation $conversation, object $settings, string $message, array $options = []): AssistantMessage
    {
        // Agregar el mensaje del usuario a las opciones para análisis de contexto
        $options['user_message'] = $message;
        $context = $this->contextBuilder->build($user, $conversation, $options);

        $history = $conversation->messages()->orderBy('created_at')->get()->map(function (AssistantMessage $message) {
            $payload = [
                'role' => $message->role,
                'content' => $message->content,
            ];

            $attachments = Arr::get($message->metadata, 'attachments', []);

            if (! empty($attachments)) {
                $payload['content'] = array_merge([
                    ['type' => 'text', 'text' => $message->content],
                ], $attachments);
            }

            return $payload;
        })->toArray();

        $systemPrompt = $this->buildSystemPrompt($context['text']);

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt],
        ], $history);

        $tools = $this->buildToolsDefinition();

        // Usar el user_id para obtener la API key desde Juntify
        $response = $this->client->createChatCompletionForUser($user->id, $messages, ['tools' => $tools, 'tool_choice' => 'auto']);

        $toolCalls = $this->client->extractToolCalls($response);

        if ($toolCalls->isNotEmpty()) {
            // Primero agregar el mensaje del asistente con los tool calls
            $choice = Arr::first($response['choices']);
            $assistantMessage = $choice['message'];
            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantMessage['content'] ?? null,
                'tool_calls' => $assistantMessage['tool_calls'] ?? []
            ];

            // Luego agregar las respuestas de los tools
            foreach ($toolCalls as $toolCall) {
                $messages[] = $this->handleToolCall($user, $conversation, $toolCall);
            }

            $response = $this->client->createChatCompletionForUser($user->id, $messages);
        }

        $content = $this->client->extractMessageContent($response);

        if (blank($content)) {
            throw new RuntimeException('El asistente no pudo generar una respuesta válida.');
        }

        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
        ]);
    }

    protected function handleToolCall(User $user, AssistantConversation $conversation, array $toolCall): array
    {
        $name = Arr::get($toolCall, 'function.name');
        $arguments = json_decode(Arr::get($toolCall, 'function.arguments', '{}'), true) ?: [];

        return match ($name) {
            'schedule_calendar_event' => $this->handleScheduleEventTool($user, $conversation, $arguments, Arr::get($toolCall, 'id')),
            default => [
                'role' => 'tool',
                'tool_call_id' => Arr::get($toolCall, 'id'),
                'content' => 'La función solicitada no está implementada.',
            ],
        };
    }

    protected function handleScheduleEventTool(User $user, AssistantConversation $conversation, array $arguments, ?string $toolCallId): array
    {
        $title = Arr::get($arguments, 'title');
        $start = Arr::get($arguments, 'start');
        $end = Arr::get($arguments, 'end');
        $description = Arr::get($arguments, 'description');
        $attendees = Arr::wrap(Arr::get($arguments, 'attendees', []));

        if (! $title || ! $start || ! $end) {
            return [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => 'No se proporcionaron datos suficientes para programar el evento.',
            ];
        }

        try {
            $event = $this->calendarService->createEvent($user, [
                'summary' => $title,
                'start' => $start,
                'end' => $end,
                'description' => $description,
                'attendees' => $attendees,
            ]);
        } catch (\Throwable $exception) {
            Log::error('No se pudo programar el evento desde el asistente.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => 'Ocurrió un error al intentar programar el evento en Google Calendar.',
            ];
        }

        return [
            'role' => 'tool',
            'tool_call_id' => $toolCallId,
            'content' => json_encode([
                'status' => 'success',
                'event' => $event,
            ], JSON_THROW_ON_ERROR),
        ];
    }

    protected function buildSystemPrompt(?string $context = null): string
    {
        $base = 'Eres el asistente inteligente de DDU. Usa exclusivamente el contexto de reuniones, documentos y eventos del calendario proporcionados. Mantén el contexto de la conversación y ofrece respuestas en español. Si no tienes datos suficientes, indícalo.';

        if ($context) {
            $base .= "\n\nContexto disponible:\n" . $context;
        }

        $currentYear = Carbon::now()->year;
        $base .= "\n\nCuando el usuario solicite agendar una reunión o evento:\n" .
                 "1. SIEMPRE revisa la FECHA Y HORA ACTUAL en el contexto proporcionado\n" .
                 "2. Calcula correctamente fechas relativas (mañana, pasado mañana, etc.)\n" .
                 "3. NUNCA uses años anteriores - SIEMPRE usa el año actual ({$currentYear})\n" .
                 "4. Utiliza la función de programación para crear el evento en Google Calendar";

        return $base;
    }

    protected function buildToolsDefinition(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'schedule_calendar_event',
                    'description' => 'Programa un evento en el Google Calendar del usuario. IMPORTANTE: Siempre utiliza el año actual (' . Carbon::now()->year . ') y calcula fechas relativas basándote en la fecha actual proporcionada en el contexto.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Título o resumen del evento.',
                            ],
                            'start' => [
                                'type' => 'string',
                                'description' => 'Fecha y hora de inicio en formato ISO 8601 (ej: ' . Carbon::now()->year . '-10-31T11:00:00-06:00). DEBE usar el año actual ' . Carbon::now()->year . '.',
                            ],
                            'end' => [
                                'type' => 'string',
                                'description' => 'Fecha y hora de finalización en formato ISO 8601 (ej: ' . Carbon::now()->year . '-10-31T12:00:00-06:00). DEBE usar el año actual ' . Carbon::now()->year . '.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción detallada del evento.',
                            ],
                            'attendees' => [
                                'type' => 'array',
                                'description' => 'Lista de correos electrónicos de asistentes.',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        'required' => ['title', 'start', 'end'],
                    ],
                ],
            ],
        ];
    }

    public function registerDocument(AssistantConversation $conversation, AssistantDocument $document): AssistantMessage
    {
        $description = "He analizado el documento {$document->original_name}.";

        if ($document->summary) {
            $description .= ' Resumen: ' . Str::of($document->summary)->squish();
        } elseif ($document->extracted_text) {
            $description .= ' Extracto: ' . Str::of($document->extracted_text)->squish()->limit(200);
        }

        $metadata = [];

        $imagePreview = Arr::get($document->metadata, 'image_preview');

        if ($imagePreview) {
            $metadata['attachments'] = [[
                'type' => 'image_url',
                'image_url' => ['url' => $imagePreview],
            ]];
        }

        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Generar respuesta del asistente usando Juntify para almacenamiento
     * 
     * @param User $user
     * @param int $conversationId ID de la conversación en Juntify
     * @param object $settings Configuración del asistente
     * @param string $message Mensaje del usuario
     * @param array $options Opciones adicionales
     * @param JuntifyConversationAdapter $adapter Adaptador para operaciones con Juntify
     * @return string Contenido de la respuesta
     */
    public function generateAssistantReplyWithJuntify(
        User $user,
        int $conversationId,
        object $settings,
        string $message,
        array $options,
        JuntifyConversationAdapter $adapter
    ): string {
        // Agregar el mensaje del usuario a las opciones para análisis de contexto
        $options['user_message'] = $message;
        
        // Construir contexto basado en opciones (reuniones, contenedores, calendario)
        $contextText = $this->contextBuilder->buildForJuntify($user, $options);
        
        // Obtener historial de mensajes de la conversación
        $messagesCollection = $adapter->getMessages($conversationId, $user->id);
        
        $history = $messagesCollection->map(function ($msg) {
            $payload = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];

            if (isset($msg->metadata) && is_array($msg->metadata)) {
                $attachments = Arr::get($msg->metadata, 'attachments', []);
                if (!empty($attachments)) {
                    $payload['content'] = array_merge([
                        ['type' => 'text', 'text' => $msg->content],
                    ], $attachments);
                }
            }

            return $payload;
        })->toArray();

        $systemPrompt = $this->buildSystemPrompt($contextText);

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt],
        ], $history);

        $tools = $this->buildToolsDefinition();

        // Usar el user_id para obtener la API key desde Juntify
        $response = $this->client->createChatCompletionForUser($user->id, $messages, ['tools' => $tools, 'tool_choice' => 'auto']);

        $toolCalls = $this->client->extractToolCalls($response);

        if ($toolCalls->isNotEmpty()) {
            // Primero agregar el mensaje del asistente con los tool calls
            $choice = Arr::first($response['choices']);
            $assistantMessage = $choice['message'];
            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantMessage['content'] ?? null,
                'tool_calls' => $assistantMessage['tool_calls'] ?? []
            ];

            // Luego agregar las respuestas de los tools
            foreach ($toolCalls as $toolCall) {
                $messages[] = $this->handleToolCallForJuntify($user, $conversationId, $toolCall, $adapter);
            }

            $response = $this->client->createChatCompletionForUser($user->id, $messages);
        }

        $content = $this->client->extractMessageContent($response);

        if (blank($content)) {
            throw new RuntimeException('El asistente no pudo generar una respuesta válida.');
        }

        // Guardar respuesta en Juntify
        $adapter->addMessage($conversationId, $user->id, 'assistant', $content);

        return $content;
    }

    /**
     * Manejar tool call para conversaciones de Juntify
     */
    protected function handleToolCallForJuntify(User $user, int $conversationId, array $toolCall, JuntifyConversationAdapter $adapter): array
    {
        $name = Arr::get($toolCall, 'function.name');
        $arguments = json_decode(Arr::get($toolCall, 'function.arguments', '{}'), true) ?: [];

        return match ($name) {
            'schedule_calendar_event' => $this->handleScheduleEventToolForJuntify($user, $conversationId, $arguments, Arr::get($toolCall, 'id'), $adapter),
            default => [
                'role' => 'tool',
                'tool_call_id' => Arr::get($toolCall, 'id'),
                'content' => 'La función solicitada no está implementada.',
            ],
        };
    }

    /**
     * Manejar herramienta de agendar eventos para Juntify
     */
    protected function handleScheduleEventToolForJuntify(User $user, int $conversationId, array $arguments, ?string $toolCallId, JuntifyConversationAdapter $adapter): array
    {
        $title = Arr::get($arguments, 'title');
        $start = Arr::get($arguments, 'start');
        $end = Arr::get($arguments, 'end');
        $description = Arr::get($arguments, 'description');
        $attendees = Arr::wrap(Arr::get($arguments, 'attendees', []));

        if (!$title || !$start || !$end) {
            return [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => 'No se proporcionaron datos suficientes para programar el evento.',
            ];
        }

        try {
            $event = $this->calendarService->createEvent($user, [
                'summary' => $title,
                'start' => $start,
                'end' => $end,
                'description' => $description,
                'attendees' => $attendees,
            ]);
        } catch (\Throwable $exception) {
            Log::error('No se pudo programar el evento desde el asistente (Juntify).', [
                'user_id' => $user->id,
                'conversation_id' => $conversationId,
                'message' => $exception->getMessage(),
            ]);

            return [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => 'Ocurrió un error al intentar programar el evento en Google Calendar.',
            ];
        }

        return [
            'role' => 'tool',
            'tool_call_id' => $toolCallId,
            'content' => json_encode([
                'status' => 'success',
                'event' => $event,
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
