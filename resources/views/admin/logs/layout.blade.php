<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-env" content="{{ app()->environment() }}">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth

    <title>@yield('title', 'Frontend Logs Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .log-level-debug { @apply bg-gray-100 text-gray-800 border-l-4 border-gray-400; }
        .log-level-info { @apply bg-blue-100 text-blue-800 border-l-4 border-blue-400; }
        .log-level-warn { @apply bg-yellow-100 text-yellow-800 border-l-4 border-yellow-400; }
        .log-level-error { @apply bg-red-100 text-red-800 border-l-4 border-red-400; }
        
        .badge-debug { @apply bg-gray-200 text-gray-800; }
        .badge-info { @apply bg-blue-200 text-blue-800; }
        .badge-warn { @apply bg-yellow-200 text-yellow-800; }
        .badge-error { @apply bg-red-200 text-red-800; }
        
        .json-viewer {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-semibold text-gray-900">Frontend Logs Dashboard</h1>
                        </div>
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="{{ route('admin.logs.dashboard') }}" 
                               class="@if(request()->routeIs('admin.logs.dashboard')) bg-blue-100 text-blue-700 @else text-gray-600 hover:text-gray-900 @endif px-3 py-2 rounded-md text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.logs.logs') }}" 
                               class="@if(request()->routeIs('admin.logs.logs')) bg-blue-100 text-blue-700 @else text-gray-600 hover:text-gray-900 @endif px-3 py-2 rounded-md text-sm font-medium">
                                Logs
                            </a>
                            <a href="{{ route('admin.logs.errors') }}" 
                               class="@if(request()->routeIs('admin.logs.errors')) bg-blue-100 text-blue-700 @else text-gray-600 hover:text-gray-900 @endif px-3 py-2 rounded-md text-sm font-medium">
                                Errors
                            </a>
                            <a href="{{ route('admin.logs.performance') }}" 
                               class="@if(request()->routeIs('admin.logs.performance')) bg-blue-100 text-blue-700 @else text-gray-600 hover:text-gray-900 @endif px-3 py-2 rounded-md text-sm font-medium">
                                Performance
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-sm text-gray-600">
                            <button onclick="if(window.logger) window.logger.info('Admin viewing logs dashboard')" 
                                    class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs">
                                Logger Active
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>