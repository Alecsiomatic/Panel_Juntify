<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para comunicación con API de Juntify
 */
class JuntifyApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('JUNTIFY_API_URL', 'https://juntify.com/api');
    }

    /**
     * Obtener lista de usuarios disponibles en Juntify
     * 
     * @param string|null $search Término de búsqueda
     * @param int|null $excludeEmpresaId Excluir usuarios de esta empresa
     * @return array
     */
    public function getUsersList(?string $search = null, ?int $excludeEmpresaId = null): array
    {
        try {
            $params = [];
            
            if ($search) {
                $params['search'] = $search;
            }
            
            if ($excludeEmpresaId) {
                $params['exclude_empresa_id'] = $excludeEmpresaId;
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/users/list", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::warning('Error al obtener usuarios de Juntify', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener usuarios',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener usuarios de Juntify: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Añadir usuario a una empresa en Juntify
     * 
     * @param string $userId UUID del usuario
     * @param int $empresaId ID de la empresa
     * @param string $rol Rol del usuario (admin, miembro, administrador)
     * @return array
     */
    public function addUserToCompany(string $userId, int $empresaId, string $rol): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/users/add-to-company", [
                'user_id' => $userId,
                'empresa_id' => $empresaId,
                'rol' => $rol
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorData = $response->json();
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al añadir usuario a la empresa',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al añadir usuario a empresa: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener detalles de reunión usando endpoint DDU
     * 
     * @param int|string $meetingId ID de la reunión
     * @param string $username Username del usuario
     * @return array
     */
    public function getDduMeetingDetails(string $meetingId, string $username): array
    {
        try {
            // Construir headers de autenticación si están configurados
            $headers = [];
            $apiToken = env('JUNTIFY_API_TOKEN');
            $panelId = env('JUNTIFY_PANEL_ID', 'panel_ddu');
            
            if ($apiToken) {
                $headers['Authorization'] = 'Bearer ' . $apiToken;
            }
            $headers['X-Panel-ID'] = $panelId;
            
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get(
                    "{$this->baseUrl}/ddu/meetings/{$meetingId}",
                    ['username' => $username]
                );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Reunión no encontrada',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener detalles DDU de reunión', [
                'meeting_id' => $meetingId,
                'username' => $username,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener detalles de la reunión',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener detalles DDU: ' . $e->getMessage(), [
                'meeting_id' => $meetingId
            ]);
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener detalles completos de una reunión (con permisos)
     * 
     * @param string $meetingId UUID de la reunión
     * @param string|null $userId UUID del usuario para verificar permisos
     * @return array
     */
    public function getMeetingDetailsComplete(string $meetingId, ?string $userId = null): array
    {
        try {
            $params = [];
            
            if ($userId) {
                $params['user_id'] = $userId;
            }

            $response = Http::timeout(15)->get(
                "{$this->baseUrl}/meetings/{$meetingId}/details",
                $params
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Reunión no encontrada',
                    'status' => 404
                ];
            }

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para acceder a esta reunión',
                    'status' => 403
                ];
            }

            Log::warning('Error al obtener detalles de reunión', [
                'meeting_id' => $meetingId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener detalles de la reunión',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener detalles de reunión: ' . $e->getMessage(), [
                'meeting_id' => $meetingId
            ]);
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar usuario contra Juntify (ya existente)
     * 
     * @param string $email
     * @param string $password
     * @param string $nombreEmpresa
     * @return array
     */
    public function validateUser(string $email, string $password, string $nombreEmpresa): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/auth/validate-user", [
                'email' => $email,
                'password' => $password,
                'nombre_empresa' => $nombreEmpresa
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error de autenticación',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al validar usuario: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify'
            ];
        }
    }

    /**
     * Obtener miembros de una empresa desde Juntify
     * 
     * @param int $empresaId ID de la empresa
     * @param bool $includeOwner Incluir al dueño de la empresa
     * @return array
     */
    public function getCompanyMembers(int $empresaId, bool $includeOwner = true): array
    {
        try {
            $params = ['include_owner' => $includeOwner ? 'true' : 'false'];

            $response = Http::timeout(10)->get(
                "{$this->baseUrl}/companies/{$empresaId}/members",
                $params
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Empresa no encontrada',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener miembros de empresa', [
                'empresa_id' => $empresaId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener miembros de la empresa',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener miembros de empresa: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar rol de un miembro de la empresa
     * 
     * @param int $empresaId ID de la empresa
     * @param string $userId UUID del usuario
     * @param string $rol Nuevo rol
     * @return array
     */
    public function updateMemberRole(int $empresaId, string $userId, string $rol): array
    {
        try {
            $response = Http::timeout(10)->patch(
                "{$this->baseUrl}/companies/{$empresaId}/members/{$userId}/role",
                ['rol' => $rol]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorData = $response->json();
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al actualizar rol',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar rol de miembro: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar un miembro de la empresa
     * 
     * @param int $empresaId ID de la empresa
     * @param string $userId UUID del usuario
     * @return array
     */
    public function removeMember(int $empresaId, string $userId): array
    {
        try {
            $response = Http::timeout(10)->delete(
                "{$this->baseUrl}/companies/{$empresaId}/members/{$userId}"
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorData = $response->json();
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al eliminar miembro',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar miembro: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener contactos de un usuario de Juntify
     * 
     * @param string $userId UUID del usuario
     * @param int|null $excludeEmpresaId Excluir usuarios de esta empresa
     * @return array
     */
    public function getUserContacts(string $userId, ?int $excludeEmpresaId = null): array
    {
        try {
            $params = [];
            
            if ($excludeEmpresaId) {
                $params['exclude_empresa_id'] = $excludeEmpresaId;
            }

            $response = Http::timeout(10)->get(
                "{$this->baseUrl}/users/{$userId}/contacts",
                $params
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Usuario no encontrado',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener contactos de usuario', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener contactos',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener contactos: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener reuniones de un usuario
     * 
     * @param string $userId UUID del usuario
     * @param array $params Parámetros opcionales: limit, offset, order_by, order_dir
     * @return array
     */
    public function getUserMeetings(string $userId, array $params = []): array
    {
        try {
            $response = Http::timeout(10)->get(
                "{$this->baseUrl}/users/{$userId}/meetings",
                $params
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Usuario no encontrado',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener reuniones del usuario', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener reuniones',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener reuniones: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener grupos de reuniones de un usuario
     * 
     * @param string $userId UUID del usuario
     * @param bool $includeMembers Incluir lista de miembros
     * @param bool $includeMeetingsCount Incluir conteo de reuniones
     * @return array
     */
    public function getUserMeetingGroups(string $userId, bool $includeMembers = true, bool $includeMeetingsCount = true): array
    {
        try {
            $params = [
                'include_members' => $includeMembers ? 'true' : 'false',
                'include_meetings_count' => $includeMeetingsCount ? 'true' : 'false'
            ];

            $response = Http::timeout(10)->get(
                "{$this->baseUrl}/users/{$userId}/meeting-groups",
                $params
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Usuario no encontrado',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener grupos del usuario', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener grupos',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener grupos: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener detalles básicos de una reunión específica
     * 
     * @param int $meetingId ID de la reunión
     * @return array
     */
    public function getMeetingDetails(int $meetingId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/meetings/{$meetingId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Reunión no encontrada',
                    'status' => 404
                ];
            }

            Log::warning('Error al obtener detalles de reunión', [
                'meeting_id' => $meetingId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener detalles de reunión',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener detalles de reunión: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Descargar archivo .ju y audio de una reunión
     * Juntify maneja la descarga usando el token del usuario
     * 
     * @param int $meetingId ID de la reunión
     * @param string $username Username del dueño de la reunión
     * @param string $fileType Tipo de archivo: 'transcript', 'audio' o 'both'
     * @return array Contiene 'file_content' (base64) o 'download_url'
     */
    public function downloadMeetingFile(int $meetingId, string $username, string $fileType = 'transcript'): array
    {
        try {
            $response = Http::timeout(60)->get(
                "{$this->baseUrl}/meetings/{$meetingId}/download/{$fileType}",
                ['username' => $username]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado',
                    'status' => 404
                ];
            }

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para descargar este archivo',
                    'status' => 403
                ];
            }

            Log::warning('Error al descargar archivo de reunión', [
                'meeting_id' => $meetingId,
                'username' => $username,
                'file_type' => $fileType,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al descargar archivo',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al descargar archivo de reunión: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tipo de una reunión (personal, organizacional, compartida)
     * 
     * @param int $meetingId ID de la reunión
     * @return array
     */
    public function getMeetingType(int $meetingId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/meetings/{$meetingId}/type");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Reunión no encontrada',
                    'status' => 404
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener tipo de reunión',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener tipo de reunión: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tipos de múltiples reuniones (batch)
     * 
     * @param array $meetingIds Array de IDs de reuniones
     * @return array
     */
    public function getMeetingTypes(array $meetingIds): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/meetings/types", [
                'meeting_ids' => $meetingIds
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener tipos de reuniones',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener tipos de reuniones: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // GRUPOS DE EMPRESA
    // ==========================================

    /**
     * Obtener grupos de una empresa
     */
    public function getCompanyGroups(int $empresaId, bool $onlyActive = true): array
    {
        try {
            $params = ['only_active' => $onlyActive ? 'true' : 'false'];
            $response = Http::timeout(15)->get("{$this->baseUrl}/companies/{$empresaId}/groups", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener grupos',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener grupos de empresa: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear un nuevo grupo en la empresa
     */
    public function createCompanyGroup(int $empresaId, string $nombre, ?string $descripcion, string $createdBy): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/companies/{$empresaId}/groups", [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'created_by' => $createdBy
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear grupo',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al crear grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener detalles de un grupo
     */
    public function getCompanyGroup(int $empresaId, int $grupoId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/companies/{$empresaId}/groups/{$grupoId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Grupo no encontrado',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar un grupo
     */
    public function deleteCompanyGroup(int $empresaId, int $grupoId): array
    {
        try {
            $response = Http::timeout(15)->delete("{$this->baseUrl}/companies/{$empresaId}/groups/{$grupoId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al eliminar grupo',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // MIEMBROS DE GRUPO
    // ==========================================

    /**
     * Añadir miembro a un grupo
     */
    public function addGroupMember(int $grupoId, string $userId, string $rol = 'colaborador'): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/groups/{$grupoId}/members", [
                'user_id' => $userId,
                'rol' => $rol
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al añadir miembro',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al añadir miembro a grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar rol de miembro en grupo
     */
    public function updateGroupMemberRole(int $grupoId, int $memberId, string $rol): array
    {
        try {
            $response = Http::timeout(15)->put("{$this->baseUrl}/groups/{$grupoId}/members/{$memberId}", [
                'rol' => $rol
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al actualizar rol',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar rol de miembro: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar miembro de grupo
     */
    public function removeGroupMember(int $grupoId, int $memberId): array
    {
        try {
            $response = Http::timeout(15)->delete("{$this->baseUrl}/groups/{$grupoId}/members/{$memberId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al eliminar miembro',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar miembro de grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // COMPARTIR REUNIONES CON GRUPOS
    // ==========================================

    /**
     * Compartir reunión con un grupo
     */
    public function shareMeetingWithGroup(int $grupoId, int $meetingId, string $sharedBy, array $permisos = [], ?string $mensaje = null, ?string $expiresAt = null): array
    {
        try {
            $data = [
                'meeting_id' => $meetingId,
                'shared_by' => $sharedBy,
                'permisos' => $permisos ?: [
                    'ver_audio' => true,
                    'ver_transcript' => true,
                    'descargar' => true
                ]
            ];

            if ($mensaje) {
                $data['mensaje'] = $mensaje;
            }
            if ($expiresAt) {
                $data['expires_at'] = $expiresAt;
            }

            $response = Http::timeout(15)->post("{$this->baseUrl}/groups/{$grupoId}/share-meeting", $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al compartir reunión',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al compartir reunión: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener reuniones compartidas de un grupo
     */
    public function getGroupSharedMeetings(int $grupoId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/groups/{$grupoId}/shared-meetings");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener reuniones compartidas',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener reuniones compartidas: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dejar de compartir reunión con grupo
     */
    public function unshareMeetingFromGroup(int $grupoId, int $meetingId): array
    {
        try {
            $response = Http::timeout(15)->delete("{$this->baseUrl}/groups/{$grupoId}/shared-meetings/{$meetingId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al dejar de compartir',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al dejar de compartir reunión: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener grupos a los que pertenece un usuario
     */
    public function getUserCompanyGroups(string $userId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/users/{$userId}/company-groups");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener grupos del usuario',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener grupos del usuario: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Descargar archivos de reunión compartida con un grupo
     * Usa autorización delegada (token del usuario que compartió)
     * 
     * @param int $empresaId ID de la empresa
     * @param int $grupoId ID del grupo
     * @param int $meetingId ID de la reunión
     * @param string $requesterUserId UUID del usuario que solicita
     * @param string $fileType Tipo de archivo: transcript, audio, both
     * @return array
     */
    public function downloadSharedMeetingFiles(int $empresaId, int $grupoId, int $meetingId, string $requesterUserId, string $fileType = 'both'): array
    {
        try {
            $response = Http::timeout(120)->get(
                "{$this->baseUrl}/companies/{$empresaId}/groups/{$grupoId}/shared-meetings/{$meetingId}/files",
                [
                    'requester_user_id' => $requesterUserId,
                    'file_type' => $fileType
                ]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al descargar archivos',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al descargar archivos compartidos: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un grupo existente
     * 
     * @param int $empresaId ID de la empresa
     * @param int $grupoId ID del grupo
     * @param array $data Datos a actualizar (nombre, descripcion, is_active)
     * @return array
     */
    public function updateCompanyGroup(int $empresaId, int $grupoId, array $data): array
    {
        try {
            $response = Http::timeout(15)->put("{$this->baseUrl}/companies/{$empresaId}/groups/{$grupoId}", $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al actualizar grupo',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar grupo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // CONFIGURACIÓN DEL ASISTENTE DDU
    // ==========================================

    /**
     * Obtener configuración del asistente para un usuario
     * 
     * @param string $userId UUID del usuario
     * @return array
     */
    public function getAssistantSettings(string $userId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/ddu/assistant-settings/{$userId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener configuración del asistente',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener configuración del asistente: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guardar configuración del asistente
     * 
     * @param string $userId UUID del usuario
     * @param array $settings Configuración (openai_api_key, enable_drive_calendar)
     * @return array
     */
    public function saveAssistantSettings(string $userId, array $settings): array
    {
        try {
            $payload = array_merge(['user_id' => $userId], $settings);

            $response = Http::timeout(10)->post("{$this->baseUrl}/ddu/assistant-settings", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null,
                    'message' => $response->json()['message'] ?? 'Configuración guardada'
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al guardar configuración',
                'errors' => $response->json()['errors'] ?? [],
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al guardar configuración del asistente: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener API key del asistente (desencriptada)
     * 
     * @param string $userId UUID del usuario
     * @return array
     */
    public function getAssistantApiKey(string $userId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/ddu/assistant-settings/{$userId}/api-key");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'API key no configurada',
                    'status' => 404
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener API key',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener API key del asistente: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar API key del asistente
     * 
     * @param string $userId UUID del usuario
     * @return array
     */
    public function deleteAssistantApiKey(string $userId): array
    {
        try {
            $response = Http::timeout(10)->delete("{$this->baseUrl}/ddu/assistant-settings/{$userId}/api-key");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? 'API key eliminada'
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al eliminar API key',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar API key del asistente: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // CONVERSACIONES DEL ASISTENTE DDU
    // ==========================================

    /**
     * Listar conversaciones del usuario
     * 
     * @param string $userId UUID del usuario
     * @return array
     */
    public function listConversations(string $userId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/ddu/assistant/conversations", [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'total' => $response->json()['total'] ?? 0
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener conversaciones',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al listar conversaciones: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear nueva conversación
     * 
     * @param string $userId UUID del usuario
     * @param string $title Título de la conversación
     * @return array
     */
    public function createConversation(string $userId, string $title = 'Nueva conversación'): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/ddu/assistant/conversations", [
                'user_id' => $userId,
                'title' => $title
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear conversación',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al crear conversación: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener conversación con mensajes
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @return array
     */
    public function getConversation(int $conversationId, string $userId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}", [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Conversación no encontrada',
                    'status' => 404
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener conversación',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener conversación: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @param array $data Datos a actualizar (title)
     * @return array
     */
    public function updateConversation(int $conversationId, string $userId, array $data): array
    {
        try {
            $payload = array_merge(['user_id' => $userId], $data);
            $response = Http::timeout(10)->put("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null,
                    'message' => $response->json()['message'] ?? 'Conversación actualizada'
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al actualizar conversación',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar conversación: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @return array
     */
    public function deleteConversation(int $conversationId, string $userId): array
    {
        try {
            $response = Http::timeout(10)->delete("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}", [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? 'Conversación eliminada'
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al eliminar conversación',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar conversación: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // MENSAJES DEL ASISTENTE DDU
    // ==========================================

    /**
     * Obtener mensajes de una conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @param int $limit Límite de mensajes
     * @param int $offset Offset para paginación
     * @return array
     */
    public function getMessages(int $conversationId, string $userId, int $limit = 100, int $offset = 0): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}/messages", [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'pagination' => $response->json()['pagination'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener mensajes',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener mensajes: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Agregar mensaje a conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @param string $role Rol del mensaje (system, user, assistant, tool)
     * @param string $content Contenido del mensaje
     * @param array|null $metadata Metadatos opcionales
     * @return array
     */
    public function addMessage(int $conversationId, string $userId, string $role, string $content, ?array $metadata = null): array
    {
        try {
            $payload = [
                'user_id' => $userId,
                'role' => $role,
                'content' => $content
            ];

            if ($metadata !== null) {
                $payload['metadata'] = $metadata;
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}/messages", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al agregar mensaje',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al agregar mensaje: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // DOCUMENTOS DEL ASISTENTE DDU
    // ==========================================

    /**
     * Obtener documentos de una conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @return array
     */
    public function getDocuments(int $conversationId, string $userId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}/documents", [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener documentos',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener documentos: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Subir documento a conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param string $userId UUID del usuario
     * @param \Illuminate\Http\UploadedFile $file Archivo a subir
     * @param string|null $extractedText Texto extraído
     * @param string|null $summary Resumen del documento
     * @return array
     */
    public function uploadDocument(int $conversationId, string $userId, $file, ?string $extractedText = null, ?string $summary = null): array
    {
        try {
            $request = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->attach('user_id', $userId);

            if ($extractedText) {
                $request = $request->attach('extracted_text', $extractedText);
            }

            if ($summary) {
                $request = $request->attach('summary', $summary);
            }

            $response = Http::timeout(60)->asMultipart()->post(
                "{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}/documents",
                [
                    ['name' => 'user_id', 'contents' => $userId],
                    ['name' => 'file', 'contents' => fopen($file->getRealPath(), 'r'), 'filename' => $file->getClientOriginalName()],
                    ['name' => 'extracted_text', 'contents' => $extractedText ?? ''],
                    ['name' => 'summary', 'contents' => $summary ?? ''],
                ]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al subir documento',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al subir documento: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar documento de conversación
     * 
     * @param int $conversationId ID de la conversación
     * @param int $documentId ID del documento
     * @param string $userId UUID del usuario
     * @return array
     */
    public function deleteDocument(int $conversationId, int $documentId, string $userId): array
    {
        try {
            $response = Http::timeout(10)->delete("{$this->baseUrl}/ddu/assistant/conversations/{$conversationId}/documents/{$documentId}", [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? 'Documento eliminado'
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al eliminar documento',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar documento: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    // ==========================================
    // TASK API METHODS
    // ==========================================

    /**
     * Get tasks for a user
     * 
     * @param string $userId UUID del usuario
     * @param array $filters Filtros opcionales
     * @return array
     */
    public function getTasks(string $userId, array $filters = []): array
    {
        try {
            $params = ['user_id' => $userId];
            
            // Add optional filters
            foreach (['status', 'priority', 'meeting_id', 'start_date', 'end_date', 'limit', 'offset'] as $key) {
                if (!empty($filters[$key])) {
                    $params[$key] = $filters[$key];
                }
            }

            $response = Http::timeout(15)->get("{$this->baseUrl}/ddu/tasks", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::warning('Error al obtener tareas de Juntify', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener tareas',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener tareas: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a new task
     * 
     * @param array $data Task data
     * @return array
     */
    public function createTask(array $data): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/ddu/tasks", $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorData = $response->json();
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al crear tarea',
                'errors' => $errorData['errors'] ?? [],
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al crear tarea: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a task
     * 
     * @param int $taskId Task ID
     * @param array $data Task data to update
     * @return array
     */
    public function updateTask(int $taskId, array $data): array
    {
        try {
            $response = Http::timeout(15)->put("{$this->baseUrl}/ddu/tasks/{$taskId}", $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorData = $response->json();
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al actualizar tarea',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar tarea: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a task
     * 
     * @param int $taskId Task ID
     * @return array
     */
    public function deleteTask(int $taskId): array
    {
        try {
            $response = Http::timeout(15)->delete("{$this->baseUrl}/ddu/tasks/{$taskId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al eliminar tarea',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar tarea: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark a task as completed
     * 
     * @param int $taskId Task ID
     * @return array
     */
    public function completeTask(int $taskId): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/ddu/tasks/{$taskId}/complete");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al completar tarea',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al completar tarea: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error de conexión con Juntify: ' . $e->getMessage()
            ];
        }
    }
}
