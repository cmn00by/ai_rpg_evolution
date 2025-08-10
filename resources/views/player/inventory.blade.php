<x-player-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventaire') }}
            </h2>
            <div class="text-sm text-gray-600">
                {{ $items->total() }} objet(s) au total
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('player.inventory') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Rarity Filter -->
                    <div>
                        <label for="rarity_id" class="block text-sm font-medium text-gray-700 mb-1">Rareté</label>
                        <select name="rarity_id" id="rarity_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les raretés</option>
                            @foreach($rarities as $rarity)
                                <option value="{{ $rarity->id }}" {{ request('rarity_id') == $rarity->id ? 'selected' : '' }}>
                                    {{ $rarity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Slot Filter -->
                    <div>
                        <label for="slot_id" class="block text-sm font-medium text-gray-700 mb-1">Slot</label>
                        <select name="slot_id" id="slot_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les slots</option>
                            @foreach($slots as $slot)
                                <option value="{{ $slot->id }}" {{ request('slot_id') == $slot->id ? 'selected' : '' }}>
                                    {{ $slot->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Equipped Filter -->
                    <div>
                        <label for="equipped" class="block text-sm font-medium text-gray-700 mb-1">État</label>
                        <select name="equipped" id="equipped" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="1" {{ request('equipped') === '1' ? 'selected' : '' }}>Équipés</option>
                            <option value="0" {{ request('equipped') === '0' ? 'selected' : '' }}>Non équipés</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                        <div class="flex">
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom de l'objet..." class="block w-full rounded-l-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                🔍
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Grid -->
        @if($items->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($items as $item)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <!-- Item Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ $item->objet->name }}</h3>
                                    @if($item->objet->rareteObjet)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" 
                                              style="background-color: {{ $item->objet->rareteObjet->color_hex }}20; color: {{ $item->objet->rareteObjet->color_hex }}">
                                            {{ $item->objet->rareteObjet->name }}
                                        </span>
                                    @endif
                                </div>
                                @if($item->is_equipped)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ⚔️ Équipé
                                    </span>
                                @endif
                            </div>

                            <!-- Item Details -->
                            <div class="space-y-2 text-sm text-gray-600">
                                @if($item->objet->slotEquipement)
                                    <div class="flex justify-between">
                                        <span>Slot:</span>
                                        <span class="font-medium">{{ $item->objet->slotEquipement->name }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex justify-between">
                                    <span>Quantité:</span>
                                    <span class="font-medium">{{ $item->quantite }}</span>
                                </div>

                                @if($item->durability_current !== null)
                                    <div class="flex justify-between">
                                        <span>Durabilité:</span>
                                        <span class="font-medium {{ $item->durability_current <= 0 ? 'text-red-600' : ($item->durability_current <= ($item->objet->base_durability * 0.3) ? 'text-yellow-600' : 'text-green-600') }}">
                                            {{ $item->durability_current }}/{{ $item->objet->base_durability }}
                                        </span>
                                    </div>
                                    
                                    <!-- Durability Bar -->
                                    @if($item->objet->base_durability > 0)
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            @php
                                                $percentage = ($item->durability_current / $item->objet->base_durability) * 100;
                                                $barColor = $percentage <= 0 ? 'bg-red-500' : ($percentage <= 30 ? 'bg-yellow-500' : 'bg-green-500');
                                            @endphp
                                            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-300" style="width: {{ max(0, min(100, $percentage)) }}%"></div>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($item->is_equipped)
                                    <form method="POST" action="{{ route('player.inventory.unequip') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                        <button type="submit" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                            📦 Déséquiper
                                        </button>
                                    </form>
                                @else
                                    @if($item->durability_current === null || $item->durability_current > 0)
                                        <form method="POST" action="{{ route('player.inventory.equip') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                                ⚔️ Équiper
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if($item->durability_current !== null && $item->durability_current < $item->objet->base_durability)
                                    <button onclick="openRepairModal({{ $item->id }}, '{{ $item->objet->name }}', {{ $item->durability_current }}, {{ $item->objet->base_durability }})" 
                                            class="inline-flex items-center px-3 py-1 border border-yellow-300 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                                        🔧 Réparer
                                    </button>
                                @endif

                                <a href="{{ route('player.inventory.show', $item) }}" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    👁️ Détails
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $items->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">🎒</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Inventaire vide</h3>
                    <p class="text-gray-500 mb-4">Vous n'avez aucun objet correspondant aux filtres sélectionnés.</p>
                    <a href="{{ route('player.shops') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Visiter les boutiques
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Repair Modal -->
    <div id="repair-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">🔧 Réparer l'objet</h3>
                <div id="repair-content" class="mb-4">
                    <!-- Content will be filled by JavaScript -->
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeRepairModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Annuler
                    </button>
                    <form id="repair-form" method="POST" action="{{ route('player.inventory.repair') }}" class="inline">
                        @csrf
                        <input type="hidden" name="item_id" id="repair-item-id">
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            Réparer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openRepairModal(itemId, itemName, currentDurability, maxDurability) {
            const modal = document.getElementById('repair-modal');
            const content = document.getElementById('repair-content');
            const itemIdInput = document.getElementById('repair-item-id');
            
            const repairCost = Math.ceil((maxDurability - currentDurability) * 10); // 10 gold per durability point
            
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <span class="font-medium">Objet:</span> ${itemName}
                    </div>
                    <div>
                        <span class="font-medium">Durabilité actuelle:</span> ${currentDurability}/${maxDurability}
                    </div>
                    <div>
                        <span class="font-medium">Coût de réparation:</span> 
                        <span class="text-yellow-600 font-bold">${repairCost} or</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        La réparation restaurera complètement la durabilité de l'objet.
                    </div>
                </div>
            `;
            
            itemIdInput.value = itemId;
            modal.classList.remove('hidden');
        }
        
        function closeRepairModal() {
            const modal = document.getElementById('repair-modal');
            modal.classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('repair-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRepairModal();
            }
        });
        
        // Auto-submit form on filter change
        document.querySelectorAll('#rarity_id, #slot_id, #equipped').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    </script>
</x-player-layout>