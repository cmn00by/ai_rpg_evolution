<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Espace Joueur</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('player.dashboard') }}" class="text-xl font-bold text-gray-800">
                                🎮 {{ config('app.name', 'RPG') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <x-nav-link :href="route('player.dashboard')" :active="request()->routeIs('player.dashboard')">
                                {{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.inventory')" :active="request()->routeIs('player.inventory*')">
                                {{ __('Inventaire') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.shops')" :active="request()->routeIs('player.shops*')">
                                {{ __('Boutiques') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.history')" :active="request()->routeIs('player.history*')">
                                {{ __('Historique') }}
                            </x-nav-link>
                        </div>
                    </div>

                    <!-- Character Selector & User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <!-- Character Selector -->
                        @if(auth()->user()->activeCharacter)
                            <div class="flex items-center mr-4 px-3 py-2 bg-blue-50 rounded-lg">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">{{ auth()->user()->activeCharacter->name }}</div>
                                    <div class="text-gray-500 flex items-center">
                                        <span class="mr-2">💰 {{ number_format(auth()->user()->activeCharacter->gold) }}</span>
                                        <span>⭐ Niv. {{ auth()->user()->activeCharacter->level }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Settings Dropdown -->
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="ml-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('characters.index')">
                                    {{ __('Mes Personnages') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profil') }}
                                </x-dropdown-link>
                                @can('access-admin')
                                    <x-dropdown-link href="/admin" target="_blank">
                                        {{ __('Administration') }}
                                    </x-dropdown-link>
                                @endcan
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Déconnexion') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link :href="route('player.dashboard')" :active="request()->routeIs('player.dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('player.inventory')" :active="request()->routeIs('player.inventory*')">
                        {{ __('Inventaire') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('player.shops')" :active="request()->routeIs('player.shops*')">
                        {{ __('Boutiques') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('player.history')" :active="request()->routeIs('player.history*')">
                        {{ __('Historique') }}
                    </x-responsive-nav-link>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
                    @if(auth()->user()->activeCharacter)
                        <div class="px-4 py-2 bg-gray-50">
                            <div class="font-medium text-base text-gray-800">{{ auth()->user()->activeCharacter->name }}</div>
                            <div class="font-medium text-sm text-gray-500">
                                💰 {{ number_format(auth()->user()->activeCharacter->gold) }} | ⭐ Niv. {{ auth()->user()->activeCharacter->level }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <x-responsive-nav-link :href="route('characters.index')">
                            {{ __('Mes Personnages') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-responsive-nav-link>
                        @can('access-admin')
                            <x-responsive-nav-link href="/admin" target="_blank">
                                {{ __('Administration') }}
                            </x-responsive-nav-link>
                        @endcan
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-responsive-nav-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Déconnexion') }}
                            </x-responsive-nav-link>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('warning') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // Toast notification system
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, 3000);
        };

        // CSRF token for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
</body>
</html>