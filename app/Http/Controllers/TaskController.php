<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TaskController extends Controller
{
    private JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Display the tasks page with Kanban and Calendar
     */
    public function index(): View
    {
        $juntifyUser = Session::get('juntify_user');
        $userId = $juntifyUser['id'] ?? null;

        return view('dashboard.tareas.index', [
            'userId' => $userId,
        ]);
    }

    /**
     * Get all tasks for the authenticated user (API endpoint)
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $juntifyUser = Session::get('juntify_user');
            $userId = $juntifyUser['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $filters = [
                'status' => $request->input('status'),
                'priority' => $request->input('priority'),
                'meeting_id' => $request->input('meeting_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'limit' => $request->input('limit', 100),
                'offset' => $request->input('offset', 0),
            ];

            $result = $this->juntifyApi->getTasks($userId, $filters);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Error al obtener tareas'
                ], $result['status'] ?? 500);
            }

            return response()->json($result['data']);

        } catch (\Exception $e) {
            Log::error('Error obteniendo tareas: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Create a new task
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $juntifyUser = Session::get('juntify_user');
            $userId = $juntifyUser['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:500',
                'description' => 'nullable|string',
                'priority' => 'nullable|in:baja,media,alta',
                'due_date' => 'nullable|date',
                'due_time' => 'nullable|string',
                'start_date' => 'nullable|date',
                'meeting_id' => 'nullable|integer',
                'assigned_user_id' => 'nullable|integer',
                'progress' => 'nullable|integer|min:0|max:100',
            ]);

            $validated['user_id'] = $userId;

            $result = $this->juntifyApi->createTask($validated);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Error al crear tarea'
                ], $result['status'] ?? 500);
            }

            return response()->json($result['data'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Update a task
     */
    public function update(Request $request, int $taskId): JsonResponse
    {
        try {
            $juntifyUser = Session::get('juntify_user');
            $userId = $juntifyUser['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:500',
                'description' => 'nullable|string',
                'priority' => 'nullable|in:baja,media,alta',
                'due_date' => 'nullable|date',
                'due_time' => 'nullable|string',
                'start_date' => 'nullable|date',
                'assigned_user_id' => 'nullable|integer',
                'progress' => 'nullable|integer|min:0|max:100',
                'status' => 'nullable|in:pending,in_progress,completed',
            ]);

            $result = $this->juntifyApi->updateTask($taskId, $validated);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Error al actualizar tarea'
                ], $result['status'] ?? 500);
            }

            return response()->json($result['data']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Delete a task
     */
    public function destroy(int $taskId): JsonResponse
    {
        try {
            $juntifyUser = Session::get('juntify_user');
            $userId = $juntifyUser['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $result = $this->juntifyApi->deleteTask($taskId);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Error al eliminar tarea'
                ], $result['status'] ?? 500);
            }

            return response()->json(['success' => true, 'message' => 'Tarea eliminada']);

        } catch (\Exception $e) {
            Log::error('Error eliminando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Mark task as completed
     */
    public function complete(int $taskId): JsonResponse
    {
        try {
            $juntifyUser = Session::get('juntify_user');
            $userId = $juntifyUser['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $result = $this->juntifyApi->completeTask($taskId);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Error al completar tarea'
                ], $result['status'] ?? 500);
            }

            return response()->json($result['data']);

        } catch (\Exception $e) {
            Log::error('Error completando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
