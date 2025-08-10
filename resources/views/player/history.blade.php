<x-player-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Historique des transactions') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_transactions'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Transactions totales</div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($stats['total_spent'] ?? 0) }} 🪙</div>
                    <div class="text-sm text-gray-600">Or dépensé</div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['total_earned'] ?? 0) }} 🪙</div>
                    <div class="text-sm text-gray-600">Or gagné</div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['favorite_shop'] ?? 'Aucune' }}</div>
                    <div class="text-sm text-gray-600">Boutique préférée</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="achat" {{ request('type') === 'achat' ? 'selected' : '' }}>Achats</option>
                            <option value="vente" {{ request('type') === 'vente' ? 'selected' : '' }}>Ventes</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="shop" class="block text-sm font-medium text-gray-700">Boutique</label>
                        <select name="shop" id="shop" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ request('shop') == $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Du</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Au</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label for="min_amount" class="block text-sm font-medium text-gray-700">Montant min</label>
                        <input type="number" name="min_amount" id="min_amount" value="{{ request('min_amount') }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filtrer
                        </button>
                        <a href="{{ route('player.history.export', request()->query()) }}" class="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            📊 CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions List -->
        @if($transactions->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Objet
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Boutique
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantité
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prix unitaire
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($transaction->type === 'achat') bg-red-100 text-red-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ $transaction->type === 'achat' ? '🛒 Achat' : '💰 Vente' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $transaction->objet->name }}</div>
                                                <div class="text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @if($transaction->objet->rarete->name === 'commun') bg-gray-100 text-gray-800
                                                        @elseif($transaction->objet->rarete->name === 'rare') bg-blue-100 text-blue-800
                                                        @elseif($transaction->objet->rarete->name === 'epique') bg-purple-100 text-purple-800
                                                        @elseif($transaction->objet->rarete->name === 'legendaire') bg-yellow-100 text-yellow-800
                                                        @endif">
                                                        {{ ucfirst($transaction->objet->rarete->name) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->boutique->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($transaction->unit_price) }} 🪙
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                        @if($transaction->type === 'achat') text-red-600
                                        @else text-green-600
                                        @endif">
                                        {{ $transaction->type === 'achat' ? '-' : '+' }}{{ number_format($transaction->total_price) }} 🪙
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="showTransactionDetails({{ $transaction->id }})" class="text-blue-600 hover:text-blue-900">
                                            Détails
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune transaction trouvée</h3>
                    <p class="text-gray-500">Aucune transaction ne correspond à vos critères de recherche.</p>
                    @if(!$activeCharacter)
                        <p class="text-gray-500 mt-2">Sélectionnez un personnage pour voir son historique.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Détails de la transaction</h3>
                    <button onclick="closeTransactionModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div id="transactionDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTransactionDetails(transactionId) {
            fetch(`{{ route('player.history.index') }}/${transactionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transaction = data.data;
                    let content = `
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">ID Transaction:</span>
                                <span class="font-medium">#${transaction.id}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-medium">${new Date(transaction.created_at).toLocaleString('fr-FR')}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Type:</span>
                                <span class="font-medium ${transaction.type === 'achat' ? 'text-red-600' : 'text-green-600'}">
                                    ${transaction.type === 'achat' ? '🛒 Achat' : '💰 Vente'}
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Objet:</span>
                                <span class="font-medium">${transaction.objet.name}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Boutique:</span>
                                <span class="font-medium">${transaction.boutique.name}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Quantité:</span>
                                <span class="font-medium">${transaction.quantity}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prix unitaire:</span>
                                <span class="font-medium">${new Intl.NumberFormat().format(transaction.unit_price)} 🪙</span>
                            </div>
                            
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-600">Total:</span>
                                <span class="font-medium text-lg ${transaction.type === 'achat' ? 'text-red-600' : 'text-green-600'}">
                                    ${transaction.type === 'achat' ? '-' : '+'}${new Intl.NumberFormat().format(transaction.total_price)} 🪙
                                </span>
                            </div>
                            
                            ${transaction.gold_before !== undefined ? `
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Or avant:</span>
                                        <span class="font-medium">${new Intl.NumberFormat().format(transaction.gold_before)} 🪙</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Or après:</span>
                                        <span class="font-medium">${new Intl.NumberFormat().format(transaction.gold_after)} 🪙</span>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    document.getElementById('transactionDetailsContent').innerHTML = content;
                    document.getElementById('transactionModal').classList.remove('hidden');
                } else {
                    showToast('Erreur lors du chargement des détails', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erreur lors du chargement des détails', 'error');
            });
        }
        
        function closeTransactionModal() {
            document.getElementById('transactionModal').classList.add('hidden');
        }
        
        function showToast(message, type = 'info') {
            // Implementation depends on your toast system
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    </script>
</x-player-layout>