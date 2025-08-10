<x-player-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $shop->name }}
                </h2>
                @if($shop->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $shop->description }}</p>
                @endif
            </div>
            <a href="{{ route('player.shops.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                ← Retour aux boutiques
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Shop Info -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $shop->boutique_items_count }}</div>
                        <div class="text-gray-600">Articles disponibles</div>
                    </div>
                    @if($shop->tax_rate > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">+{{ $shop->tax_rate }}%</div>
                            <div class="text-gray-600">Taxe</div>
                        </div>
                    @endif
                    @if($shop->discount_rate > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">-{{ $shop->discount_rate }}%</div>
                            <div class="text-gray-600">Remise</div>
                        </div>
                    @endif
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $shop->max_daily_purchases }}</div>
                        <div class="text-gray-600">Limite quotidienne</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="rarity" class="block text-sm font-medium text-gray-700">Rareté</label>
                        <select name="rarity" id="rarity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes</option>
                            <option value="commun" {{ request('rarity') === 'commun' ? 'selected' : '' }}>Commun</option>
                            <option value="rare" {{ request('rarity') === 'rare' ? 'selected' : '' }}>Rare</option>
                            <option value="epique" {{ request('rarity') === 'epique' ? 'selected' : '' }}>Épique</option>
                            <option value="legendaire" {{ request('rarity') === 'legendaire' ? 'selected' : '' }}>Légendaire</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="slot" class="block text-sm font-medium text-gray-700">Emplacement</label>
                        <select name="slot" id="slot" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="tete" {{ request('slot') === 'tete' ? 'selected' : '' }}>Tête</option>
                            <option value="torse" {{ request('slot') === 'torse' ? 'selected' : '' }}>Torse</option>
                            <option value="jambes" {{ request('slot') === 'jambes' ? 'selected' : '' }}>Jambes</option>
                            <option value="pieds" {{ request('slot') === 'pieds' ? 'selected' : '' }}>Pieds</option>
                            <option value="arme" {{ request('slot') === 'arme' ? 'selected' : '' }}>Arme</option>
                            <option value="bouclier" {{ request('slot') === 'bouclier' ? 'selected' : '' }}>Bouclier</option>
                            <option value="accessoire" {{ request('slot') === 'accessoire' ? 'selected' : '' }}>Accessoire</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="max_price" class="block text-sm font-medium text-gray-700">Prix max</label>
                        <input type="number" name="max_price" id="max_price" value="{{ request('max_price') }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Prix maximum">
                    </div>
                    
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nom de l'objet">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Items Grid -->
        @if($items->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($items as $item)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <!-- Item Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900 text-sm">{{ $item->objet->name }}</h3>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($item->objet->rarete->name === 'commun') bg-gray-100 text-gray-800
                                            @elseif($item->objet->rarete->name === 'rare') bg-blue-100 text-blue-800
                                            @elseif($item->objet->rarete->name === 'epique') bg-purple-100 text-purple-800
                                            @elseif($item->objet->rarete->name === 'legendaire') bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ ucfirst($item->objet->rarete->name) }}
                                        </span>
                                        @if($item->objet->slot_equipement)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($item->objet->slot_equipement->name) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Item Stats -->
                            @if($item->objet->stats && count($item->objet->stats) > 0)
                                <div class="text-xs text-gray-600 mb-3">
                                    @foreach($item->objet->stats as $stat => $value)
                                        @if($value != 0)
                                            <div class="flex justify-between">
                                                <span>{{ ucfirst($stat) }}:</span>
                                                <span class="{{ $value > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $value > 0 ? '+' : '' }}{{ $value }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <!-- Price & Stock -->
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Prix:</span>
                                    <div class="text-right">
                                        @if($item->base_price != $item->final_price)
                                            <div class="text-xs text-gray-500 line-through">{{ number_format($item->base_price) }} 🪙</div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($item->final_price) }} 🪙</div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Stock:</span>
                                    <span class="text-sm font-medium {{ $item->stock > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $item->stock > 0 ? $item->stock : 'Épuisé' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="space-y-2">
                                @if($item->stock > 0 && $activeCharacter && $activeCharacter->gold >= $item->final_price)
                                    <button onclick="buyItem({{ $item->id }}, '{{ $item->objet->name }}', {{ $item->final_price }})" class="w-full inline-flex justify-center items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Acheter
                                    </button>
                                @else
                                    <button disabled class="w-full inline-flex justify-center items-center px-3 py-2 bg-gray-400 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest cursor-not-allowed">
                                        @if($item->stock <= 0)
                                            Épuisé
                                        @elseif(!$activeCharacter)
                                            Aucun personnage
                                        @else
                                            Or insuffisant
                                        @endif
                                    </button>
                                @endif
                                
                                <button onclick="showItemDetails({{ $item->objet->id }})" class="w-full inline-flex justify-center items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Détails
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $items->appends(request()->query())->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">📦</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun article trouvé</h3>
                    <p class="text-gray-500">Cette boutique ne contient aucun article correspondant à vos critères.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Buy Confirmation Modal -->
    <div id="buyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Confirmer l'achat</h3>
                    <button onclick="closeBuyModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Article:</span>
                        <span id="buyItemName" class="font-medium"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Prix:</span>
                        <span id="buyItemPrice" class="font-medium"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Or actuel:</span>
                        <span class="font-medium">{{ $activeCharacter ? number_format($activeCharacter->gold) : 0 }} 🪙</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-gray-600">Or après achat:</span>
                        <span id="buyGoldAfter" class="font-medium"></span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button onclick="closeBuyModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Annuler
                    </button>
                    <button id="confirmBuyBtn" onclick="confirmBuy()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        Confirmer l'achat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Details Modal -->
    <div id="itemDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Détails de l'objet</h3>
                    <button onclick="closeItemDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div id="itemDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentBuyItem = null;
        
        function buyItem(itemId, itemName, itemPrice) {
            currentBuyItem = { id: itemId, name: itemName, price: itemPrice };
            
            document.getElementById('buyItemName').textContent = itemName;
            document.getElementById('buyItemPrice').textContent = new Intl.NumberFormat().format(itemPrice) + ' 🪙';
            
            const currentGold = {{ $activeCharacter ? $activeCharacter->gold : 0 }};
            const goldAfter = currentGold - itemPrice;
            document.getElementById('buyGoldAfter').textContent = new Intl.NumberFormat().format(goldAfter) + ' 🪙';
            
            document.getElementById('buyModal').classList.remove('hidden');
        }
        
        function closeBuyModal() {
            document.getElementById('buyModal').classList.add('hidden');
            currentBuyItem = null;
        }
        
        function confirmBuy() {
            if (!currentBuyItem) return;
            
            const btn = document.getElementById('confirmBuyBtn');
            btn.disabled = true;
            btn.textContent = 'Achat en cours...';
            
            fetch(`{{ route('player.shops.buy', $shop) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    object_id: currentBuyItem.id,
                    qty: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Achat réussi !', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message || 'Erreur lors de l\'achat', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Confirmer l\'achat';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erreur lors de l\'achat', 'error');
                btn.disabled = false;
                btn.textContent = 'Confirmer l\'achat';
            });
        }
        
        function showItemDetails(objectId) {
            fetch(`/api/objects/${objectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    let content = `
                        <div class="space-y-3">
                            <div>
                                <h4 class="font-medium text-gray-900">${item.name}</h4>
                                <p class="text-sm text-gray-600">${item.description || 'Aucune description'}</p>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rareté:</span>
                                <span class="font-medium">${item.rarete?.name || 'Inconnue'}</span>
                            </div>
                            
                            ${item.slot_equipement ? `
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Emplacement:</span>
                                    <span class="font-medium">${item.slot_equipement.name}</span>
                                </div>
                            ` : ''}
                            
                            ${item.durability_max ? `
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Durabilité max:</span>
                                    <span class="font-medium">${item.durability_max}</span>
                                </div>
                            ` : ''}
                            
                            ${item.stackable ? `
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Empilable:</span>
                                    <span class="font-medium text-green-600">Oui</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    if (item.stats && Object.keys(item.stats).length > 0) {
                        content += `
                            <div class="border-t pt-3 mt-3">
                                <h5 class="font-medium text-gray-900 mb-2">Statistiques:</h5>
                                <div class="space-y-1">
                        `;
                        
                        for (const [stat, value] of Object.entries(item.stats)) {
                            if (value !== 0) {
                                content += `
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">${stat}:</span>
                                        <span class="${value > 0 ? 'text-green-600' : 'text-red-600'}">${value > 0 ? '+' : ''}${value}</span>
                                    </div>
                                `;
                            }
                        }
                        
                        content += `
                                </div>
                            </div>
                        `;
                    }
                    
                    document.getElementById('itemDetailsContent').innerHTML = content;
                    document.getElementById('itemDetailsModal').classList.remove('hidden');
                } else {
                    showToast('Erreur lors du chargement des détails', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erreur lors du chargement des détails', 'error');
            });
        }
        
        function closeItemDetailsModal() {
            document.getElementById('itemDetailsModal').classList.add('hidden');
        }
        
        function showToast(message, type = 'info') {
            // Implementation depends on your toast system
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    </script>
</x-player-layout>