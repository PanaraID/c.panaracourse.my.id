<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" id="app">
    <head>
        @include('partials.head')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 antialiased transition-colors duration-300">
        <!-- Theme Toggle Button -->
        <div class="fixed top-6 right-6 z-50">
            <button 
                type="button"
                onclick="toggleTheme()"
                class="group relative p-3 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-110"
                aria-label="Toggle theme"
                title="Toggle Dark/Light Mode"
            >
                <div class="relative w-6 h-6">
                    <!-- Sun Icon (Show in dark mode) -->
                    <svg class="absolute inset-0 w-6 h-6 text-amber-500 hidden dark:block group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <!-- Moon Icon (Show in light mode) -->
                    <svg class="absolute inset-0 w-6 h-6 text-indigo-600 block dark:hidden group-hover:-rotate-12 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute -bottom-12 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <div class="bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs py-1 px-3 rounded-lg whitespace-nowrap">
                        <span class="dark:hidden">Switch to Dark Mode</span>
                        <span class="hidden dark:inline">Switch to Light Mode</span>
                    </div>
                </div>
            </button>
        </div>

        <!-- Background Pattern -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <!-- Animated Background Orbs -->
            <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 dark:opacity-10 animate-pulse"></div>
            <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-pink-400 to-red-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 dark:opacity-10 animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 dark:opacity-10 animate-pulse" style="animation-delay: 4s;"></div>
            
            <!-- Grid Pattern -->
            <div class="absolute inset-0 bg-grid-pattern opacity-20 dark:opacity-10"></div>
        </div>

        <div class="flex min-h-screen flex-col items-center justify-center p-6 relative z-10">
            <!-- Logo and Brand -->
            <div class="mb-10 text-center">
                <a href="{{ route('home') }}" class="inline-flex flex-col items-center group" wire:navigate>
                    <!-- Logo Container with Enhanced Animation -->
                    <div class="relative mb-6">
                        <!-- Animated Ring -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-3xl blur-md opacity-60 group-hover:opacity-100 animate-pulse group-hover:animate-spin transition-all duration-700" style="animation-duration: 3s;"></div>
                        <!-- Logo Background -->
                        <div class="relative bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-2xl group-hover:shadow-3xl transition-all duration-300 group-hover:scale-105">
                            <x-app-logo-icon class="size-12 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300" />
                        </div>
                        <!-- Floating particles -->
                        <div class="absolute -top-2 -right-2 w-3 h-3 bg-blue-500 rounded-full animate-ping opacity-75"></div>
                        <div class="absolute -bottom-2 -left-2 w-2 h-2 bg-purple-500 rounded-full animate-ping opacity-75" style="animation-delay: 1s;"></div>
                    </div>
                    
                    <!-- Brand Name with Enhanced Typography -->
                    <div class="space-y-2">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400 group-hover:scale-105 transition-transform duration-300 tracking-tight">
                            Panara Course
                        </h1>
                        <div class="flex items-center justify-center space-x-2">
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-400 dark:via-gray-600 to-transparent flex-1"></div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 px-3 font-medium">
                                Platform Pembelajaran Digital
                            </p>
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-400 dark:via-gray-600 to-transparent flex-1"></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Main Content Card -->
            <div class="w-full max-w-lg">
                <div class="relative group">
                    <!-- Card Background with Enhanced Glass Effect -->
                    <div class="absolute inset-0 glass rounded-3xl shadow-2xl group-hover:shadow-3xl transition-all duration-500"></div>
                    
                    <!-- Animated Border -->
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-0 group-hover:opacity-20 blur-sm transition-all duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative p-10 space-y-2">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Enhanced Footer Links -->
                <div class="mt-10 text-center space-y-6">
                    <!-- Quick Links -->
                    <div class="flex justify-center space-x-8 text-sm">
                        <a href="#" class="group flex items-center space-x-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Bantuan</span>
                        </a>
                        <a href="#" class="group flex items-center space-x-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Privasi</span>
                        </a>
                        <a href="#" class="group flex items-center space-x-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Syarat</span>
                        </a>
                    </div>
                    
                    <!-- Copyright -->
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-500 flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span>© {{ date('Y') }} Panara Course. Hak cipta dilindungi.</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @fluxScripts
        
        <!-- Theme Management Script -->
        <script>
            // Initialize theme
            function initTheme() {
                const savedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = savedTheme || (prefersDark ? 'dark' : 'light');
                
                document.documentElement.classList.toggle('dark', theme === 'dark');
                localStorage.setItem('theme', theme);
            }

            // Toggle theme
            function toggleTheme() {
                const isDark = document.documentElement.classList.contains('dark');
                const newTheme = isDark ? 'light' : 'dark';
                
                document.documentElement.classList.toggle('dark', newTheme === 'dark');
                localStorage.setItem('theme', newTheme);
                
                // Add a subtle animation effect
                document.body.style.transition = 'background-color 0.3s ease';
                setTimeout(() => {
                    document.body.style.transition = '';
                }, 300);
            }

            // Initialize on load
            initTheme();

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    document.documentElement.classList.toggle('dark', e.matches);
                }
            });
        </script>

        <!-- Custom Styles -->
        <style>
            .bg-grid-pattern {
                background-image: 
                    linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
                background-size: 24px 24px;
            }

            .dark .bg-grid-pattern {
                background-image: 
                    linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            }

            /* Enhanced shadows */
            .shadow-3xl {
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            }

            /* Smooth glass morphism */
            .glass {
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                background: rgba(255, 255, 255, 0.8);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .dark .glass {
                background: rgba(17, 24, 39, 0.8);
                border: 1px solid rgba(55, 65, 81, 0.3);
            }

            /* Input focus styles with enhanced glow */
            .form-input:focus {
                box-shadow: 
                    0 0 0 3px rgba(99, 102, 241, 0.1),
                    0 0 20px rgba(99, 102, 241, 0.2),
                    inset 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            /* Button hover glow effect */
            .btn-glow:hover {
                box-shadow: 
                    0 10px 30px rgba(99, 102, 241, 0.3),
                    0 0 40px rgba(99, 102, 241, 0.2);
            }

            /* Floating animation */
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
            
            .float-animation {
                animation: float 3s ease-in-out infinite;
            }

            /* Pulse glow animation */
            @keyframes pulse-glow {
                0%, 100% { 
                    box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
                }
                50% { 
                    box-shadow: 0 0 40px rgba(99, 102, 241, 0.6), 
                                0 0 60px rgba(147, 51, 234, 0.3);
                }
            }

            /* Loading animation for buttons */
            .loading-dots::after {
                content: '';
                display: inline-block;
                width: 1rem;
                height: 1rem;
                background-image: url("data:image/svg+xml,%3csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='50' cy='50' r='30' fill='none' stroke='%236366f1' stroke-width='3' stroke-dasharray='50' stroke-dashoffset='50'%3e%3canimateTransform attributeName='transform' attributeType='XML' type='rotate' from='0 50 50' to='360 50 50' dur='1s' repeatCount='indefinite'/%3e%3c/circle%3e%3c/svg%3e");
                background-size: cover;
            }

            /* Smooth transitions for theme switching */
            * {
                transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
            }
        </style>

        @if(session('clear_token'))
        <script>
            // Clear user token from localStorage after logout
            localStorage.removeItem('user_token');
            console.log('✓ User token cleared from localStorage');
        </script>
        @endif
    </body>
</html>