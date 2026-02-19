<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Panel DDU') }} - Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- DDU Custom Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/dashboard.css', 'resources/js/dashboard.js', 'resources/css/ddu-modal.css'])

    <!-- Custom DDU Colors - Paleta Institucional -->
    <style>
        :root {
            --ddu-aqua: #6DDEDD;
            --ddu-lavanda: #546CB1;
            --ddu-navy-dark: #1F2A4E;
            --ddu-navy: #233771;
            --ddu-blue: #45539F;
            --ddu-purple: #6F78E4;
            --ddu-gradient: linear-gradient(135deg, #6DDEDD 0%, #546CB1 20%, #1F2A4E 40%, #233771 60%, #45539F 80%, #6F78E4 100%);
            --ddu-gradient-soft: linear-gradient(135deg, #6DDEDD 0%, #546CB1 50%, #6F78E4 100%);
            --ddu-gradient-sidebar: linear-gradient(180deg, #1F2A4E 0%, #233771 50%, #45539F 100%);
        }
    </style>
    @stack('styles')
</head>
<body class="font-lato antialiased bg-gray-50">
    <div class="h-screen flex overflow-hidden">
        <!-- Sidebar -->
        <nav class="w-64 shadow-lg border-r border-gray-200 flex-shrink-0 h-full overflow-y-auto" style="background: linear-gradient(180deg, #1F2A4E 0%, #233771 40%, #45539F 100%);">
            <!-- Logo/Brand -->
            <div class="p-6 border-b border-white/10">
                <div class="flex justify-center">
                    <div class="w-20 h-20 flex items-center justify-center">
                        <img src="{{ asset('images/Gemini_Generated_Image_wxj41rwxj41rwxj4-removebg-preview.png') }}"
                             alt="DDU Logo"
                             class="w-full h-full object-contain"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <!-- Fallback cuando la imagen no carga -->
                        <div class="w-full h-full bg-gradient-to-br from-ddu-aqua via-ddu-lavanda to-ddu-navy rounded-xl items-center justify-center text-white font-bold text-xl hidden">
                            DDU
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            @php
                $juntifyUser = session('juntify_user', []);
                $juntifyCompany = session('juntify_company', []);
                
                $userName = $juntifyUser['name'] ?? $juntifyUser['username'] ?? 'Usuario DDU';
                $userEmail = $juntifyUser['email'] ?? 'no-email';
                $userRole = $juntifyCompany['rol_usuario'] ?? 'miembro';
                
                // Normalizar rol
                if (in_array(strtolower($userRole), ['admin', 'administrator'])) {
                    $userRole = 'Administrador';
                } else {
                    $userRole = ucfirst($userRole);
                }
            @endphp

            <div class="p-4 border-b border-white/10" style="background: rgba(255, 255, 255, 0.05);">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #6DDEDD 0%, #6F78E4 100%);">
                        <span class="text-white font-semibold text-sm">
                            {{ substr($userName, 0, 1) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white text-sm truncate">{{ $userName }}</p>
                        <p class="text-xs text-white/70 truncate">
                            {{ $userRole }} DDU - {{ $userEmail }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="py-4">
                <nav class="space-y-1">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5v6m8-6v6m-8 4h8"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Reuniones -->
                    <a href="{{ route('reuniones.index') }}"
                       class="nav-item {{ request()->routeIs('reuniones.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Reuniones</span>
                    </a>

                    <!-- Tareas -->
                    <a href="{{ route('tareas.index') }}"
                       class="nav-item {{ request()->routeIs('tareas.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Tareas</span>
                    </a>

                    <!-- Mis grupos -->
                    <a href="{{ route('grupos.index') }}"
                       class="nav-item {{ request()->routeIs('grupos.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M9 7a3 3 0 106 0 3 3 0 00-6 0z"></path>
                        </svg>
                        <span>Mis grupos</span>
                    </a>

                    <!-- Asistente -->
                    <a href="{{ route('assistant.index') }}"
                       class="nav-item {{ request()->routeIs('assistant.index') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <span>Asistente</span>
                    </a>

                    <!-- Configuración del Asistente -->
                    <a href="{{ route('assistant.settings.index') }}"
                       class="nav-item {{ request()->routeIs('assistant.settings.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Configuración Asistente</span>
                    </a>

                    <!-- Administrar Miembros (Solo para administradores) -->
                    @php
                        $juntifyCompany = session('juntify_company', []);
                        $isAdmin = isset($juntifyCompany['rol_usuario']) && in_array(strtolower($juntifyCompany['rol_usuario']), ['admin', 'administrador']);
                    @endphp
                    @if($isAdmin)
                    <a href="{{ route('admin.members.index') }}"
                       class="nav-item {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Administrar Miembros</span>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Logout -->
            <div class="absolute bottom-0 w-64 p-4 border-t border-white/10" style="background: rgba(0, 0, 0, 0.2);">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="shadow-sm border-b border-gray-200 px-6 py-4" style="background: linear-gradient(90deg, #1F2A4E 0%, #233771 50%, #45539F 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-white/80 mt-1">@yield('page-description', 'Panel de control DDU')</p>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a8.38 8.38 0 010-11L18 1h-5a8.38 8.38 0 00-6 2.5A8.38 8.38 0 001 1h5l1.5 1.5a8.38 8.38 0 000 11L6 17h5a8.38 8.38 0 006-2.5A8.38 8.38 0 0023 17h-5"></path>
                            </svg>
                        </button>

                        <!-- User Menu -->
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #6DDEDD 0%, #6F78E4 100%);">
                                <span class="text-white font-semibold text-sm">{{ substr($userName ?? 'U', 0, 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
