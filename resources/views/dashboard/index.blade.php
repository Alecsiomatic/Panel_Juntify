@extends('layouts.dashboard')

@section('page-title', 'Dashboard')
@section('page-description', 'Resumen de actividades DDU')

@section('content')
<div class="space-y-6 fade-in">
    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Miembros Total -->
        <div class="stat-card primary">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number">{{ $stats['total_members'] }}</div>
                    <div class="stat-label">Miembros Activos</div>
                </div>
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Reuniones -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-gray-900">{{ $stats['recent_meetings'] }}</div>
                    <div class="stat-label text-gray-600">Reuniones Recientes</div>
                </div>
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>

        <!-- Tareas Pendientes -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-gray-900">{{ $stats['pending_tasks'] }}</div>
                    <div class="stat-label text-gray-600">Tareas Pendientes</div>
                </div>
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
        </div>

        <!-- Tu Rol -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-gray-900 text-lg capitalize">{{ $stats['user_role'] }}</div>
                    <div class="stat-label text-gray-600">Tu Rol</div>
                </div>
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Panel de Acciones -->
        <div class="ddu-card">
            <div class="ddu-card-header">
                <div>
                    <h3 class="ddu-card-title">Accesos Rápidos</h3>
                    <p class="ddu-card-subtitle">Funciones principales del sistema</p>
                </div>
            </div>

            <div class="space-y-3">
                <a href="{{ route('reuniones.index') }}" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-ddu-lavanda rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Reuniones</p>
                            <p class="text-sm text-gray-500">Gestionar reuniones y participantes</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>

                <a href="{{ route('assistant.index') }}" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-ddu-aqua rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Asistente</p>
                            <p class="text-sm text-gray-500">Asistente inteligente DDU</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>

                @if($stats['user_role'] === 'administrador')
                <a href="{{ route('admin.members.index') }}" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-ddu-lavanda to-ddu-aqua rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Administrar Miembros</p>
                            <p class="text-sm text-gray-500">Gestionar usuarios y permisos</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
                @endif
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="ddu-card">
            <div class="ddu-card-header">
                <div>
                    <h3 class="ddu-card-title">Actividad Reciente</h3>
                    <p class="ddu-card-subtitle">Últimas acciones en el sistema</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($recentActivity ?? [] as $activity)
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 {{ $activity['bg_color'] ?? 'bg-gray-100' }} rounded-full flex items-center justify-center">
                        @if($activity['type'] === 'login')
                        <svg class="w-4 h-4 {{ $activity['icon_color'] ?? 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        @elseif($activity['type'] === 'meeting')
                        <svg class="w-4 h-4 {{ $activity['icon_color'] ?? 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        @elseif($activity['type'] === 'task')
                        <svg class="w-4 h-4 {{ $activity['icon_color'] ?? 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        @else
                        <svg class="w-4 h-4 {{ $activity['icon_color'] ?? 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $activity['subtitle'] }}</p>
                    </div>
                </div>
                @empty
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Sesión iniciada como {{ $stats['user_role'] }}</p>
                        <p class="text-xs text-gray-500">{{ session('juntify_user')['email'] ?? 'N/A' }}</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="ddu-card">
        <div class="ddu-card-header">
            <div>
                <h3 class="ddu-card-title">Bienvenido al Panel DDU</h3>
                <p class="ddu-card-subtitle">Tu centro de control para la gestión de actividades</p>
            </div>
        </div>

        <div class="bg-gradient-to-r from-ddu-lavanda to-ddu-aqua rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xl font-bold mb-2">¡Hola, {{ session('juntify_user')['name'] ?? 'Usuario' }}!</h4>
                    <p class="opacity-90">Este es tu dashboard personalizado donde puedes gestionar reuniones, usar el asistente inteligente y administrar miembros del equipo DDU.</p>
                </div>
                <div class="hidden lg:block">
                    <svg class="w-20 h-20 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
