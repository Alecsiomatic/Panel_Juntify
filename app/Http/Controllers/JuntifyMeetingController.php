<?php

namespace App\Http\Controllers;

use App\Services\JuDecryptionService;
use App\Services\JuFileDecryption;
use App\Services\JuntifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class JuntifyMeetingController extends Controller
{
    protected JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Show meeting details from juntify database
     */
    public function show($meetingId): JsonResponse
    {
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;

        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $user = (object)['id' => $userId];

        try {
            // Obtener datos del usuario de Juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $user->id)
                ->first();

            if (!$juntifyUser) {
                return response()->json(['error' => 'Usuario no encontrado en Juntify.'], 404);
            }

            // Obtener la reunión desde juntify (primero permanentes, luego temporales)
            $meeting = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('id', $meetingId)
                ->where('username', $juntifyUser->username)
                ->first();

            $isTemporary = false;

            // Si no se encuentra en permanentes, buscar en temporales
            if (!$meeting) {
                $meeting = DB::connection('juntify')
                    ->table('transcription_temps')
                    ->where('id', $meetingId)
                    ->where('user_id', $user->id)
                    ->first();
                
                if ($meeting) {
                    $isTemporary = true;
                    // Normalizar campos para compatibilidad
                    $meeting->meeting_name = $meeting->title;
                    $meeting->transcript_drive_id = null;
                    $meeting->audio_drive_id = null;
                    $meeting->audio_download_url = null;
                    $meeting->transcript_download_url = null;
                }
            }

            if (!$meeting) {
                return response()->json(['error' => 'Reunión no encontrada o no tienes permisos para acceder a ella.'], 404);
            }

            // Formatear los datos de la reunión
            $meetingData = [
                'id' => $meeting->id,
                'name' => $meeting->meeting_name,
                'description' => $isTemporary ? ($meeting->description ?? null) : null,
                'status' => 'completed',
                'is_temporary' => $isTemporary,
                'started_at' => $meeting->created_at,
                'ended_at' => null,
                'duration_minutes' => $isTemporary ? ($meeting->duration ?? null) : null,
                'audio_url' => $meeting->audio_download_url ?? null,
                'transcript_url' => $meeting->transcript_download_url ?? null,
                'audio_drive_id' => $meeting->audio_drive_id ?? null,
                'transcript_drive_id' => $meeting->transcript_drive_id ?? null,
                'containers' => [],
                'groups' => [],
            ];

            return response()->json([
                'meeting' => $meetingData,
                'tasks' => [],
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo reunión de Juntify: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * Show meeting details with transcript data
     * Uses Juntify DDU API endpoint for complete meeting data
     */
    public function showDetails($transcriptionId): JsonResponse
    {
        try {
            $meetingId = $transcriptionId;
            $juntifyUserData = Session::get('juntify_user');
            $userId = $juntifyUserData['id'] ?? null;
            $username = $juntifyUserData['username'] ?? null;

            if (!$userId || !$username) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            if (!$meetingId) {
                return response()->json(['error' => 'ID de reunión no proporcionado.'], 422);
            }

            // Usar el endpoint DDU de Juntify para obtener detalles completos
            $result = $this->juntifyApi->getDduMeetingDetails($meetingId, $username);

            if (!$result['success']) {
                Log::warning('Error obteniendo detalles DDU', [
                    'meeting_id' => $meetingId,
                    'username' => $username,
                    'error' => $result['error'] ?? 'Unknown'
                ]);
                return response()->json([
                    'error' => $result['error'] ?? 'Reunión no encontrada.'
                ], $result['status'] ?? 404);
            }

            $data = $result['data'];
            
            // Nueva estructura: meeting contiene metadatos, content contiene datos procesados
            $meeting = $data['meeting'] ?? [];
            $content = $data['content'] ?? [];
            $contentError = $data['content_error'] ?? null;

            // Extraer datos del content (nueva estructura)
            $segments = $content['segments'] ?? [];
            $tasks = $content['tasks'] ?? [];
            $keyPoints = $content['key_points'] ?? [];
            $summary = $content['summary'] ?? '';
            $speakers = $content['speakers'] ?? [];

            // Normalizar segmentos
            $normalizedSegments = array_map(function($segment, $index) {
                $speaker = $segment['speaker'] ?? $segment['speaker_name'] ?? "Hablante " . ($index + 1);
                $start = floatval($segment['start'] ?? 0);
                $end = floatval($segment['end'] ?? 0);
                
                return [
                    'speaker' => $speaker,
                    'text' => $segment['text'] ?? '',
                    'start' => $start,
                    'end' => $end,
                    'timestamp' => $segment['timestamp'] ?? $this->formatTimestamp($start, $end),
                    'avatar' => strtoupper(substr($speaker, 0, 2))
                ];
            }, $segments, array_keys($segments));

            // Normalizar tareas
            $normalizedTasks = array_map(function($task, $index) {
                if (is_string($task)) {
                    return [
                        'id' => $index + 1,
                        'task' => $task,
                        'description' => '',
                        'assigned_to' => 'No asignado',
                        'priority' => 'media',
                        'progress' => 0,
                        'start_date' => null,
                        'due_date' => null,
                        'status' => 'pending'
                    ];
                }
                return [
                    'id' => $task['id'] ?? $index + 1,
                    'task' => $task['title'] ?? $task['text'] ?? $task['tarea'] ?? '',
                    'description' => $task['descripcion'] ?? $task['description'] ?? '',
                    'assigned_to' => $task['assignee'] ?? $task['asignado'] ?? 'No asignado',
                    'priority' => $task['priority'] ?? $task['prioridad'] ?? 'media',
                    'progress' => $task['progress'] ?? $task['progreso'] ?? 0,
                    'start_date' => $task['fecha_inicio'] ?? null,
                    'due_date' => $task['due_date'] ?? $task['dueDate'] ?? $task['fecha_limite'] ?? null,
                    'status' => $task['status'] ?? 'pending'
                ];
            }, $tasks, array_keys($tasks));

            // Normalizar key_points (pueden ser strings u objetos)
            $normalizedKeyPoints = array_map(function($point) {
                if (is_string($point)) {
                    return $point;
                }
                return $point['text'] ?? $point['description'] ?? (string)$point;
            }, $keyPoints);

            // Extraer IDs de archivos de la nueva estructura
            $files = $meeting['files'] ?? [];
            $audioFileId = $files['audio']['id'] ?? $meeting['audio_drive_id'] ?? null;
            $transcriptFileId = $files['ju']['id'] ?? $meeting['transcript_drive_id'] ?? null;

            // Construir URL de descarga de audio si existe el archivo
            $audioUrl = $audioFileId ? route('download.audio', $meetingId) : null;

            // Construir respuesta compatible con el frontend
            return response()->json([
                'summary' => $summary ?: 'No hay resumen disponible.',
                'key_points' => $normalizedKeyPoints,
                'segments' => $normalizedSegments,
                'audio_url' => $audioUrl,
                'audio_base64' => null,
                'is_temporary' => false,
                'tasks' => $normalizedTasks,
                'speakers' => $speakers,
                'content_error' => $contentError,
                'meeting' => [
                    'id' => $meeting['id'] ?? $meetingId,
                    'name' => $meeting['title'] ?? $meeting['meeting_name'] ?? 'Reunión',
                    'created_at' => $meeting['meeting_date'] ?? $meeting['created_at'] ?? null,
                    'duration' => $meeting['duration'] ?? null,
                    'status' => $meeting['status'] ?? 'completed',
                    'audio_drive_id' => $audioFileId,
                    'transcript_drive_id' => $transcriptFileId,
                    'google_drive_folder_id' => $meeting['google_drive_folder_id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo detalles de reunión: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al cargar los datos del servidor.'], 500);
        }
    }

    /**
     * Format timestamp from seconds to MM:SS - MM:SS
     */
    private function formatTimestamp(float $start, float $end): string
    {
        $formatTime = function($seconds) {
            $mins = floor($seconds / 60);
            $secs = floor($seconds % 60);
            return sprintf('%02d:%02d', $mins, $secs);
        };
        
        return $formatTime($start) . ' - ' . $formatTime($end);
    }
}
