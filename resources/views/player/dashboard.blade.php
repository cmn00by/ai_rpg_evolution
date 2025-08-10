<x-player-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Joueur') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Character Quick Info -->
        @if($character)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $character->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $character->classe->name ?? 'Aucune classe' }} - Niveau {{ $character->level }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ number_format($character->gold) }}</div>
                                <div class="text-xs text-gray-500">Or</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $character->experience }}</div>
                                <div class="text-xs text-gray-500">XP</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $character->reputation }}</div>
                                <div class="text-xs text-gray-500">Réputation</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Top Stats -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">📊 Top 5 Statistiques</h4>
                        <div id="top-stats-container" class="space-y-3">
                            <div class="text-center text-gray-500">Chargement...</div>
                        </div>
                    </div>
                </div>

                <!-- Equipped Items -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">⚔️ Équipements</h4>
                        <div id="equipped-items-container" class="space-y-2">
                            <div class="text-center text-gray-500">Chargement...</div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">🎒 Inventaire</h4>
                        <div id="inventory-summary-container" class="space-y-2">
                            <div class="text-center text-gray-500">Chargement...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">📜 Événements Récents</h4>
                    <div id="recent-events-container">
                        <div class="text-center text-gray-500">Chargement...</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">⚡ Actions Rapides</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="{{ route('player.inventory') }}" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="text-2xl mb-2">🎒</div>
                            <div class="text-sm font-medium text-gray-900">Inventaire</div>
                        </a>
                        <a href="{{ route('player.shops') }}" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="text-2xl mb-2">🏪</div>
                            <div class="text-sm font-medium text-gray-900">Boutiques</div>
                        </a>
                        <a href="{{ route('player.history') }}" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="text-2xl mb-2">📋</div>
                            <div class="text-sm font-medium text-gray-900">Historique</div>
                        </a>
                        <a href="{{ route('characters.index') }}" class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <div class="text-2xl mb-2">👤</div>
                            <div class="text-sm font-medium text-gray-900">Personnages</div>
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- No Active Character -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">🎭</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun personnage actif</h3>
                    <p class="text-gray-500 mb-4">Vous devez sélectionner un personnage pour accéder au dashboard.</p>
                    <a href="{{ route('characters.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Sélectionner un personnage
                    </a>
                </div>
            </div>
        @endif
    </div>

    @if($character)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        async function loadDashboardData() {
            try {
                // Load top stats
                const topStatsResponse = await fetch('{{ route("player.dashboard.top-stats") }}');
                if (topStatsResponse.ok) {
                    const topStatsData = await topStatsResponse.json();
                    displayTopStats(topStatsData.data);
                }

                // Load equipped items
                const equippedResponse = await fetch('{{ route("player.dashboard.equipped-items") }}');
                if (equippedResponse.ok) {
                    const equippedData = await equippedResponse.json();
                    displayEquippedItems(equippedData.data);
                }

                // Load inventory summary
                const inventoryResponse = await fetch('{{ route("player.dashboard.inventory-summary") }}');
                if (inventoryResponse.ok) {
                    const inventoryData = await inventoryResponse.json();
                    displayInventorySummary(inventoryData.data);
                }

                // Load recent events
                const eventsResponse = await fetch('{{ route("player.dashboard.recent-events") }}');
                if (eventsResponse.ok) {
                    const eventsData = await eventsResponse.json();
                    displayRecentEvents(eventsData.data);
                }
            } catch (error) {
                console.error('Erreur lors du chargement des données:', error);
            }
        }

        function displayTopStats(stats) {
            const container = document.getElementById('top-stats-container');
            if (stats.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500">Aucune statistique disponible</div>';
                return;
            }

            container.innerHTML = stats.map(stat => `
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">${stat.attribute}</span>
                    <div class="text-right">
                        <span class="font-medium text-gray-900">${stat.final_value}</span>
                        ${stat.equipment_bonus > 0 ? `<span class="text-xs text-green-600">(+${stat.equipment_bonus})</span>` : ''}
                    </div>
                </div>
            `).join('');
        }

        function displayEquippedItems(items) {
            const container = document.getElementById('equipped-items-container');
            if (items.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500">Aucun équipement</div>';
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">${item.slot || 'Autre'}</span>
                    <span class="font-medium text-gray-900">${item.object_name}</span>
                </div>
            `).join('');
        }

        function displayInventorySummary(summary) {
            const container = document.getElementById('inventory-summary-container');
            container.innerHTML = `
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total objets</span>
                        <span class="font-medium text-gray-900">${summary.total_items}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Équipés</span>
                        <span class="font-medium text-gray-900">${summary.equipped_items}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Types uniques</span>
                        <span class="font-medium text-gray-900">${summary.unique_objects}</span>
                    </div>
                    ${summary.broken_items > 0 ? `
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-red-600">Cassés</span>
                            <span class="font-medium text-red-600">${summary.broken_items}</span>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function displayRecentEvents(events) {
            const container = document.getElementById('recent-events-container');
            if (events.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500">Aucun événement récent</div>';
                return;
            }

            container.innerHTML = `
                <div class="space-y-3">
                    ${events.map(event => `
                        <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="text-lg">${getEventIcon(event.type)}</div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${event.description}</div>
                                <div class="text-xs text-gray-500">${formatDate(event.created_at)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function getEventIcon(type) {
            const icons = {
                'achat': '🛒',
                'vente': '💰',
                'equip': '⚔️',
                'unequip': '📦',
                'repair': '🔧'
            };
            return icons[type] || '📝';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'À l\'instant';
            if (diffMins < 60) return `Il y a ${diffMins} min`;
            if (diffHours < 24) return `Il y a ${diffHours}h`;
            if (diffDays < 7) return `Il y a ${diffDays}j`;
            return date.toLocaleDateString('fr-FR');
        }
    </script>
    @endif
</x-player-layout>