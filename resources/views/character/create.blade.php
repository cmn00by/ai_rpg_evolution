<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Créer un personnage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('character.store') }}">
                        @csrf

                        <!-- Nom du personnage -->
                        <div class="mb-4">
                            <x-input-label for="nom" :value="__('Nom du personnage')" />
                            <x-text-input id="nom" class="block mt-1 w-full" type="text" name="nom" :value="old('nom')" required autofocus autocomplete="nom" />
                            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <x-input-label for="description" :value="__('Description (optionnelle)')" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <h3 class="font-medium text-gray-900 mb-2">Statistiques de départ :</h3>
                            <ul class="text-sm text-gray-600">
                                <li>• Niveau : 1</li>
                                <li>• Or : 1000 pièces</li>
                                <li>• Réputation : 0</li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('character.select') }}" class="text-gray-600 hover:text-gray-900">
                                ← Retour à la sélection
                            </a>
                            
                            <x-primary-button>
                                {{ __('Créer le personnage') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>