<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Personnage : ') . $character->nom }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Informations générales -->
                        <div>
                            <h3 class="text-lg font-medium mb-4">Informations générales</h3>
                            <div class="space-y-2">
                                <p><strong>Nom :</strong> {{ $character->nom }}</p>
                                <p><strong>Niveau :</strong> {{ $character->niveau }}</p>
                                <p><strong>Or :</strong> {{ number_format($character->or) }} pièces</p>
                                <p><strong>Réputation :</strong> {{ $character->reputation }}</p>
                                <p><strong>Créé le :</strong> {{ $character->created_at->format('d/m/Y à H:i') }}</p>
                                @if($character->description)
                                    <p><strong>Description :</strong></p>
                                    <p class="text-gray-600 italic">{{ $character->description }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Statistiques -->
                        <div>
                            <h3 class="text-lg font-medium mb-4">Statistiques</h3>
                            <div class="space-y-2">
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-sm text-gray-600">Achats aujourd'hui</p>
                                    <p class="text-lg font-semibold">{{ $character->achats_du_jour ?? 0 }} / 10</p>
                                </div>
                                
                                @if($character->inventaire)
                                    <div class="bg-gray-50 p-3 rounded">
                                        <p class="text-sm text-gray-600">Objets en inventaire</p>
                                        <p class="text-lg font-semibold">{{ $character->inventaire->objets->count() }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('character.select') }}" class="text-gray-600 hover:text-gray-900">
                            ← Retour à la sélection
                        </a>
                        
                        @if($character->id === auth()->user()->active_character_id)
                            <span class="inline-block bg-blue-500 text-white px-4 py-2 rounded">
                                Personnage actif
                            </span>
                        @elseif($character->user_id === auth()->id())
                            <form method="POST" action="{{ route('character.set-active', $character) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Activer ce personnage
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>