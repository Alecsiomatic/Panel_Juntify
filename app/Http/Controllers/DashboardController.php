<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use App\Services\Meetings\JuntifyMeetingService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

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

        return view('dashboard.index', compact('stats'));
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
