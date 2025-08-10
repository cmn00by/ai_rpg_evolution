<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sélection de personnage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($characters->count() > 0)
                        <h3 class="text-lg font-medium mb-4">Vos personnages :</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach($characters as $character)
                                <div class="border rounded-lg p-4 {{ $character->id === auth()->user()->active_character_id ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                                    <h4 class="font-semibold text-lg">{{ $character->nom }}</h4>
                                    <p class="text-gray-600">Niveau {{ $character->niveau }}</p>
                                    <p class="text-gray-600">{{ number_format($character->or) }} pièces d'or</p>
                                    
                                    @if($character->id === auth()->user()->active_character_id)
                                        <span class="inline-block bg-blue-500 text-white px-2 py-1 rounded text-sm mt-2">
                                            Personnage actif
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('character.set-active', $character) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                Sélectionner
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-600 mb-4">Vous n'avez pas encore de personnage.</p>
                    @endif

                    @if($characters->count() < 5)
                        <a href="{{ route('character.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Créer un nouveau personnage
                        </a>
                    @else
                        <p class="text-gray-600">Vous avez atteint la limite de 5 personnages.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>