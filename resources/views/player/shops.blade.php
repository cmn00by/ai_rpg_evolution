<x-player-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Boutiques') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if($shops->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($shops as $shop)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <!-- Shop Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $shop->name }}</h3>
                                    @if($shop->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $shop->description }}</p>
                                    @endif
                                </div>
                                <div class="text-2xl">🏪</div>
                            </div>

                            <!-- Shop Stats -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Articles disponibles:</span>
                                    <span class="font-medium text-gray-900">{{ $shop->boutique_items_count }}</span>
                                </div>
                                
                                @if($shop->tax_rate > 0)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Taxe:</span>
                                        <span class="font-medium text-red-600">+{{ $shop->tax_rate }}%</span>
                                    </div>
                                @endif
                                
                                @if($shop->discount_rate > 0)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Remise:</span>
                                        <span class="font-medium text-green-600">-{{ $shop->discount_rate }}%</span>
                                    </div>
                                @endif
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Limite quotidienne:</span>
                                    <span class="font-medium text-gray-900">{{ $shop->max_daily_purchases }} achats</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6">
                                <a href="{{ route('player.shops.show', $shop) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Visiter la boutique
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">🏪</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune boutique disponible</h3>
                    <p class="text-gray-500">Il n'y a actuellement aucune boutique active.</p>
                </div>
            </div>
        @endif
    </div>
</x-player-layout>