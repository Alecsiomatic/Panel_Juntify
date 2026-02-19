<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use App\Services\Meetings\JuntifyMeetingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(JuntifyApiService $juntifyApiService)
    {
        // Obtener datos del usuario desde sesión Juntify
        $juntifyUser = Session::get('juntify_user', []);
        $juntifyCompany = Session::get('juntify_company', []);
        
        // Obtener rol del usuario
        $userRole = $juntifyCompany['rol_usuario'] ?? 'miembro';
        
        // Normalizar rol en español
        if (in_array(strtolower($userRole), ['admin', 'administrator'])) {
            $userRole = 'administrador';
        }

        // Obtener ID de empresa DDU (por defecto 3)
        $empresaId = $juntifyCompany['empresa_id'] ?? 3;
        $userId = $juntifyUser['id'] ?? null;

        // Obtener estadísticas reales
        $totalMembers = 0;
        $recentMeetings = 0;
        $pendingTasks = 0;

        // 1. Obtener total de miembros de DDU
        try {
            $membersResult = $juntifyApiService->getCompanyMembers($empresaId);
            if ($membersResult['success'] && isset($membersResult['data']['members'])) {
                $totalMembers = count($membersResult['data']['members']);
            }
        } catch (\Exception $e) {
            Log::warning('Error al obtener miembros para dashboard: ' . $e->getMessage());
        }

        // 2. Obtener reuniones recientes del usuario
        if ($userId) {
            try {
                $meetingsResult = $juntifyApiService->getUserMeetings($userId, ['limit' => 100]);
                if ($meetingsResult['success'] && isset($meetingsResult['data']['meetings'])) {
                    $recentMeetings = count($meetingsResult['data']['meetings']);
                } elseif ($meetingsResult['success'] && isset($meetingsResult['data']['data'])) {
                    $recentMeetings = count($meetingsResult['data']['data']);
                } elseif ($meetingsResult['success'] && is_array($meetingsResult['data'])) {
                    $recentMeetings = count($meetingsResult['data']);
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener reuniones para dashboard: ' . $e->getMessage());
            }

            // 3. Obtener tareas pendientes (consulta directa a juntify DB)
            try {
                $pendingTasks = \DB::connection('juntify')
                    ->table('tasks_laravel')
                    ->where('username', $juntifyUser['username'] ?? '')
                    ->where('progreso', '<', 100)
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener tareas para dashboard: ' . $e->getMessage());
                $pendingTasks = 0;
            }
        }

        $stats = [
            'total_members' => $totalMembers,
            'recent_meetings' => $recentMeetings,
            'pending_tasks' => $pendingTasks,
            'user_role' => $userRole
        ];

        // 4. Generar actividad reciente
        $recentActivity = $this->getRecentActivity($juntifyUser, $juntifyApiService, $userId);

        return view('dashboard.index', compact('stats', 'recentActivity'));
    }

    /**
     * Obtener actividad reciente del usuario
     */
    private function getRecentActivity(array $juntifyUser, JuntifyApiService $juntifyApiService, ?string $userId): array
    {
        $activity = [];

        // 1. Login actual
        $authTime = Session::get('juntify_auth_time');
        if ($authTime) {
            $loginTime = \Carbon\Carbon::createFromTimestamp($authTime);
            $activity[] = [
                'type' => 'login',
                'title' => 'Sesión iniciada',
                'subtitle' => $loginTime->diffForHumans(),
                'bg_color' => 'bg-blue-100',
                'icon_color' => 'text-blue-600',
                'timestamp' => $authTime
            ];
        }

        // 2. Últimas reuniones
        if ($userId) {
            try {
                $meetingsResult = $juntifyApiService->getUserMeetings($userId, ['limit' => 3, 'order_by' => 'created_at', 'order_dir' => 'desc']);
                $meetings = [];
                
                if ($meetingsResult['success']) {
                    if (isset($meetingsResult['data']['meetings'])) {
                        $meetings = $meetingsResult['data']['meetings'];
                    } elseif (isset($meetingsResult['data']['data'])) {
                        $meetings = $meetingsResult['data']['data'];
                    } elseif (is_array($meetingsResult['data'])) {
                        $meetings = array_slice($meetingsResult['data'], 0, 3);
                    }
                }

                foreach ($meetings as $meeting) {
                    $meetingDate = $meeting['created_at'] ?? $meeting['date'] ?? null;
                    $meetingTitle = $meeting['title'] ?? $meeting['name'] ?? 'Reunión';
                    
                    $subtitle = 'Sin fecha';
                    if ($meetingDate) {
                        try {
                            $subtitle = \Carbon\Carbon::parse($meetingDate)->diffForHumans();
                        } catch (\Exception $e) {
                            $subtitle = $meetingDate;
                        }
                    }

                    $activity[] = [
                        'type' => 'meeting',
                        'title' => 'Reunión: ' . \Str::limit($meetingTitle, 30),
                        'subtitle' => $subtitle,
                        'bg_color' => 'bg-green-100',
                        'icon_color' => 'text-green-600',
                        'timestamp' => strtotime($meetingDate ?? 'now')
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener reuniones recientes: ' . $e->getMessage());
            }

            // 3. Últimas tareas completadas
            try {
                $recentTasks = \DB::connection('juntify')
                    ->table('tasks_laravel')
                    ->where('username', $juntifyUser['username'] ?? '')
                    ->orderBy('updated_at', 'desc')
                    ->limit(2)
                    ->get();

                foreach ($recentTasks as $task) {
                    $taskDate = $task->updated_at ?? $task->created_at ?? null;
                    $subtitle = 'Sin fecha';
                    if ($taskDate) {
                        try {
                            $subtitle = \Carbon\Carbon::parse($taskDate)->diffForHumans();
                        } catch (\Exception $e) {
                            $subtitle = $taskDate;
                        }
                    }

                    $status = ($task->progreso ?? 0) >= 100 ? 'Completada' : 'En progreso (' . ($task->progreso ?? 0) . '%)';

                    $activity[] = [
                        'type' => 'task',
                        'title' => 'Tarea: ' . \Str::limit($task->tarea ?? 'Sin título', 25),
                        'subtitle' => $status . ' - ' . $subtitle,
                        'bg_color' => ($task->progreso ?? 0) >= 100 ? 'bg-green-100' : 'bg-yellow-100',
                        'icon_color' => ($task->progreso ?? 0) >= 100 ? 'text-green-600' : 'text-yellow-600',
                        'timestamp' => strtotime($taskDate ?? 'now')
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener tareas recientes: ' . $e->getMessage());
            }
        }

        // Ordenar por timestamp (más reciente primero)
        usort($activity, function ($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });

        // Limitar a 5 elementos
        return array_slice($activity, 0, 5);
    }

    /**
     * Reuniones index
     */
    public function reuniones(JuntifyMeetingService $juntifyMeetingService)
    {
        // Obtener datos del usuario desde sesión Juntify
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;
        $username = $juntifyUserData['username'] ?? null;

        if (!$userId) {
            return redirect()->route('login')->withErrors(['message' => 'Usuario no autenticado']);
        }

        // Crear objeto de usuario compatible con el servicio
        $user = (object)[
            'id' => $userId,
            'username' => $username,
            'email' => $juntifyUserData['email'] ?? null
        ];

        // Obtener reuniones desde Juntify API
        [$meetings, $stats] = $juntifyMeetingService->getOverviewForUser($user);

        // Obtener grupos del usuario desde Juntify API
        $userGroups = $juntifyMeetingService->getUserGroups($user);

        // Google Token no está disponible con API (se maneja en Juntify)
        $googleToken = null;

        return view('dashboard.reuniones.index', compact('stats', 'meetings', 'googleToken', 'userGroups'));
    }

}
