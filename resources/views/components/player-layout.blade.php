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
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Left side - Navigation Links -->
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('player.dashboard') }}" class="text-xl font-bold text-gray-800">
                                🎮 {{ config('app.name', 'RPG Game') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <x-nav-link :href="route('player.dashboard')" :active="request()->routeIs('player.dashboard')">
                                {{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.inventory.index')" :active="request()->routeIs('player.inventory.*')">
                                {{ __('Inventaire') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.shops.index')" :active="request()->routeIs('player.shops.*')">
                                {{ __('Boutiques') }}
                            </x-nav-link>
                            <x-nav-link :href="route('player.history.index')" :active="request()->routeIs('player.history.*')">
                                {{ __('Historique') }}
                            </x-nav-link>
                        </div>
                    </div>

                    <!-- Right side - Character Selector & User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <!-- Character Selector -->
                        @if(auth()->user()->characters->count() > 0)
                            <div class="mr-6">
                                <form method="POST" action="{{ route('player.dashboard.switch-character') }}" id="character-form">
                                    @csrf
                                    <select name="character_id" onchange="document.getElementById('character-form').submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Sélectionner un personnage</option>
                                        @foreach(auth()->user()->characters as $character)
                                            <option value="{{ $character->id }}" 
                                                {{ session('active_character_id') == $character->id ? 'selected' : '' }}>
                                                {{ $character->name }} (Niv. {{ $character->level }}) - {{ number_format($character->gold) }} 🪙
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        @endif

                        <!-- Active Character Info -->
                        @if($activeCharacter = auth()->user()->characters->find(session('active_character_id')))
                            <div class="mr-6 text-sm">
                                <div class="font-medium text-gray-700">{{ $activeCharacter->name }}</div>
                                <div class="text-gray-500">
                                    Niv. {{ $activeCharacter->level }} • {{ number_format($activeCharacter->gold) }} 🪙
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
                                    <x-dropdown-link :href="route('admin.dashboard')">
                                        {{ __('Administration') }}
                                    </x-dropdown-link>
                                @endcan

                                <!-- Authentication -->
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
                    <x-responsive-nav-link :href="route('player.inventory.index')" :active="request()->routeIs('player.inventory.*')">
                        {{ __('Inventaire') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('player.shops.index')" :active="request()->routeIs('player.shops.*')">
                        {{ __('Boutiques') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('player.history.index')" :active="request()->routeIs('player.history.*')">
                        {{ __('Historique') }}
                    </x-responsive-nav-link>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
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
                            <x-responsive-nav-link :href="route('admin.dashboard')">
                                {{ __('Administration') }}
                            </x-responsive-nav-link>
                        @endcan

                        <!-- Authentication -->
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
        <main class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('warning') }}</span>
                    </div>
                @endif

                <!-- No Active Character Warning -->
                @if(!session('active_character_id') && auth()->user()->characters->count() > 0)
                    <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">
                            <strong>Aucun personnage sélectionné.</strong> Veuillez sélectionner un personnage dans le menu déroulant ci-dessus pour accéder à toutes les fonctionnalités.
                        </span>
                    </div>
                @endif

                @if(auth()->user()->characters->count() === 0)
                    <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">
                            <strong>Aucun personnage créé.</strong> 
                            <a href="{{ route('characters.index') }}" class="underline hover:text-yellow-900">
                                Créez votre premier personnage
                            </a> pour commencer à jouer.
                        </span>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2">
        <!-- Toasts will be inserted here -->
    </div>

    <script>
        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-black',
                info: 'bg-blue-500 text-white'
            };
            
            toast.className = `${colors[type]} px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;
            toast.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }, duration);
        }
        
        // Global error handler for fetch requests
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            showToast('Une erreur inattendue s\'est produite', 'error');
        });
        
        // CSRF token setup for all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.csrfToken = token.getAttribute('content');
        }
    </script>

    @stack('scripts')
</body>
</html>