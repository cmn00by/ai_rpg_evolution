<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personnage;
use App\Models\InventairePersonnage;
use App\Models\Boutique;
use App\Models\BoutiqueItem;
use App\Models\AchatHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PlayerApiController extends Controller
{
    /**
     * Récupère les informations du personnage actif
     */
    public function getCharacter()
    {
        $user = Auth::user();
        $character = $user->activeCharacter;
        
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun personnage actif sélectionné.'
            ], 400);
        }

        // Charger les relations nécessaires
        $character->load([
            'classe',
            'personnageAttributsCache.attribut',
            'inventairePersonnages' => function ($query) {
                $query->where('is_equipped', true)
                    ->with(['objet.rarete', 'objet.slot']);
            }
        ]);

        // Formater les stats finales
        $stats = $character->personnageAttributsCache->map(function ($stat) {
            return [
                'attribute' => $stat->attribut->name,
                'base_value' => $stat->valeur_base,
                'final_value' => $stat->valeur_finale,
                'equipment_bonus' => $stat->valeur_finale - $stat->valeur_base
            ];
        });

        // Formater les équipements
        $equipment = $character->inventairePersonnages->map(function ($item) {
            return [
                'id' => $item->id,
                'object' => [
                    'id' => $item->objet->id,
                    'name' => $item->objet->name,
                    'rarity' => $item->objet->rarete->name ?? null,
                    'slot' => $item->objet->slot->name ?? null
                ],
                'quantity' => $item->quantite,
                'durability' => $item->durability_current
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'character' => [
                    'id' => $character->id,
                    'name' => $character->name,
                    'level' => $character->level,
                    'experience' => $character->experience,
                    'gold' => $character->gold,
                    'reputation' => $character->reputation,
                    'class' => $character->classe->name ?? null
                ],
                'stats' => $stats,
                'equipment' => $equipment
            ]
        ]);
    }

    /**
     * Récupère l'inventaire du personnage
     */
    public function getInventory(Request $request)
    {
        $user = Auth::user();
        $character = $user->activeCharacter;
        
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun personnage actif sélectionné.'
            ], 400);
        }

        $query = InventairePersonnage::where('personnage_id', $character->id)
            ->with(['objet.rarete', 'objet.slot']);

        // Filtres
        if ($request->filled('equipped')) {
            $query->where('is_equipped', $request->boolean('equipped'));
        }

        if ($request->filled('rarity_id')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('rarete_objet_id', $request->rarity_id);
            });
        }

        if ($request->filled('slot_id')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('slot_equipement_id', $request->slot_id);
            });
        }

        $inventory = $query->orderBy('is_equipped', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $formattedInventory = $inventory->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'object' => [
                    'id' => $item->objet->id,
                    'name' => $item->objet->name,
                    'rarity' => [
                        'id' => $item->objet->rarete->id ?? null,
                        'name' => $item->objet->rarete->name ?? null,
                        'color' => $item->objet->rarete->color_hex ?? null
                    ],
                    'slot' => [
                        'id' => $item->objet->slot->id ?? null,
                        'name' => $item->objet->slot->name ?? null
                    ],
                    'stackable' => $item->objet->stackable,
                    'base_durability' => $item->objet->base_durability
                ],
                'quantity' => $item->quantite,
                'is_equipped' => $item->is_equipped,
                'durability_current' => $item->durability_current,
                'created_at' => $item->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedInventory,
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total()
            ]
        ]);
    }

    /**
     * Équipe un objet
     */
    public function equipItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventaire_personnages,id'
        ]);

        $user = Auth::user();
        $character = $user->activeCharacter;
        
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun personnage actif sélectionné.'
            ], 400);
        }

        $item = InventairePersonnage::where('id', $request->item_id)
            ->where('personnage_id', $character->id)
            ->with('objet.slot')
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Objet non trouvé dans votre inventaire.'
            ], 404);
        }

        if ($item->is_equipped) {
            return response()->json([
                'success' => false,
                'message' => 'Cet objet est déjà équipé.'
            ], 400);
        }

        if ($item->durability_current !== null && $item->durability_current <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cet objet est cassé et ne peut pas être équipé.'
            ], 400);
        }

        try {
            DB::transaction(function () use ($item, $character) {
                $slot = $item->objet->slot;
                
                if ($slot) {
                    $currentEquipped = InventairePersonnage::where('personnage_id', $character->id)
                        ->where('is_equipped', true)
                        ->whereHas('objet', function ($q) use ($slot) {
                            $q->where('slot_equipement_id', $slot->id);
                        })
                        ->count();

                    if ($currentEquipped >= $slot->max_per_slot) {
                        throw new \Exception("Limite d'équipement atteinte pour ce slot ({$slot->max_per_slot}).");
                    }
                }

                if ($item->objet->stackable && $item->quantite > 1) {
                    InventairePersonnage::create([
                        'personnage_id' => $character->id,
                        'objet_id' => $item->objet_id,
                        'quantite' => 1,
                        'is_equipped' => true,
                        'durability_current' => $item->durability_current
                    ]);

                    $item->update(['quantite' => $item->quantite - 1]);
                } else {
                    $item->update(['is_equipped' => true]);
                }

                $character->invalidateStatsCache();
            });

            return response()->json([
                'success' => true,
                'message' => "Objet {$item->objet->name} équipé avec succès.",
                'meta' => [
                    'gold_after' => $character->fresh()->gold
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Déséquipe un objet
     */
    public function unequipItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventaire_personnages,id'
        ]);

        $user = Auth::user();
        $character = $user->activeCharacter;
        
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun personnage actif sélectionné.'
            ], 400);
        }

        $item = InventairePersonnage::where('id', $request->item_id)
            ->where('personnage_id', $character->id)
            ->with('objet')
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Objet non trouvé dans votre inventaire.'
            ], 404);
        }

        if (!$item->is_equipped) {
            return response()->json([
                'success' => false,
                'message' => 'Cet objet n\'est pas équipé.'
            ], 400);
        }

        try {
            DB::transaction(function () use ($item, $character) {
                if ($item->objet->stackable) {
                    $existingStack = InventairePersonnage::where('personnage_id', $character->id)
                        ->where('objet_id', $item->objet_id)
                        ->where('is_equipped', false)
                        ->where('id', '!=', $item->id)
                        ->first();

                    if ($existingStack) {
                        $existingStack->update([
                            'quantite' => $existingStack->quantite + $item->quantite
                        ]);
                        $item->delete();
                    } else {
                        $item->update(['is_equipped' => false]);
                    }
                } else {
                    $item->update(['is_equipped' => false]);
                }

                $character->invalidateStatsCache();
            });

            return response()->json([
                'success' => true,
                'message' => "Objet {$item->objet->name} déséquipé avec succès."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Récupère la liste des boutiques
     */
    public function getShops()
    {
        $shops = Boutique::where('is_active', true)
            ->withCount('boutiqueItems')
            ->orderBy('name')
            ->get()
            ->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'description' => $shop->description,
                    'tax_rate' => $shop->tax_rate,
                    'discount_rate' => $shop->discount_rate,
                    'max_daily_purchases' => $shop->max_daily_purchases,
                    'items_count' => $shop->boutique_items_count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $shops
        ]);
    }

    /**
     * Récupère le catalogue d'une boutique
     */
    public function getShopCatalog(Request $request, Boutique $boutique)
    {
        if (!$boutique->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cette boutique n\'est pas disponible.'
            ], 404);
        }

        $query = BoutiqueItem::where('boutique_id', $boutique->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with(['objet.rarete', 'objet.slot']);

        // Filtres
        if ($request->filled('rarity_id')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('rarete_objet_id', $request->rarity_id);
            });
        }

        if ($request->filled('slot_id')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('slot_equipement_id', $request->slot_id);
            });
        }

        $items = $query->orderBy('base_price')
            ->paginate($request->get('per_page', 20));

        $user = Auth::user();
        $character = $user->activeCharacter;

        $formattedItems = $items->getCollection()->map(function ($item) use ($boutique, $character) {
            $finalPrice = $this->calculateFinalPrice($item, $boutique, $character);
            
            return [
                'id' => $item->id,
                'object' => [
                    'id' => $item->objet->id,
                    'name' => $item->objet->name,
                    'rarity' => [
                        'id' => $item->objet->rarete->id ?? null,
                        'name' => $item->objet->rarete->name ?? null,
                        'color' => $item->objet->rarete->color_hex ?? null
                    ],
                    'slot' => [
                        'id' => $item->objet->slot->id ?? null,
                        'name' => $item->objet->slot->name ?? null
                    ]
                ],
                'stock_quantity' => $item->stock_quantity,
                'base_price' => $item->base_price,
                'final_price' => $finalPrice,
                'can_afford' => $character ? $character->gold >= $finalPrice : false
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'shop' => [
                    'id' => $boutique->id,
                    'name' => $boutique->name,
                    'tax_rate' => $boutique->tax_rate,
                    'discount_rate' => $boutique->discount_rate
                ]
            ]
        ]);
    }

    /**
     * Achète un objet dans une boutique
     */
    public function buyItem(Request $request, Boutique $boutique, BoutiqueItem $item)
    {
        // Rate limiting pour les achats
        $key = 'buy_item:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives d\'achat. Veuillez patienter.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 10 tentatives par minute

        $request->validate([
            'quantity' => 'required|integer|min:1|max:99'
        ]);

        $user = Auth::user();
        $character = $user->activeCharacter;
        
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun personnage actif sélectionné.'
            ], 400);
        }

        if (!$boutique->is_active || !$item->is_active || $item->boutique_id !== $boutique->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cet objet n\'est pas disponible.'
            ], 400);
        }

        $quantity = $request->quantity;

        if ($item->stock_quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant. Stock disponible: {$item->stock_quantity}"
            ], 400);
        }

        // Vérifier les limites quotidiennes
        $dailyPurchases = AchatHistorique::where('personnage_id', $character->id)
            ->where('boutique_id', $boutique->id)
            ->where('type_transaction', 'achat')
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($dailyPurchases >= $boutique->max_daily_purchases) {
            return response()->json([
                'success' => false,
                'message' => 'Limite d\'achats quotidiens atteinte pour cette boutique.'
            ], 400);
        }

        $unitPrice = $this->calculateFinalPrice($item, $boutique, $character);
        $totalPrice = $unitPrice * $quantity;

        if ($character->gold < $totalPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Fonds insuffisants.',
                'meta' => [
                    'required' => $totalPrice,
                    'available' => $character->gold,
                    'missing' => $totalPrice - $character->gold
                ]
            ], 400);
        }

        try {
            DB::transaction(function () use ($character, $boutique, $item, $quantity, $unitPrice, $totalPrice) {
                $goldBefore = $character->gold;
                
                $character->update(['gold' => $character->gold - $totalPrice]);
                $item->update(['stock_quantity' => $item->stock_quantity - $quantity]);
                
                $this->addToInventory($character, $item->objet, $quantity);
                
                AchatHistorique::create([
                    'personnage_id' => $character->id,
                    'boutique_id' => $boutique->id,
                    'objet_id' => $item->objet_id,
                    'quantite' => $quantity,
                    'prix_unitaire' => $unitPrice,
                    'prix_total' => $totalPrice,
                    'type_transaction' => 'achat',
                    'meta_json' => json_encode([
                        'gold_before' => $goldBefore,
                        'gold_after' => $character->gold,
                        'tax_rate' => $boutique->tax_rate,
                        'discount_rate' => $boutique->discount_rate,
                        'base_price' => $item->base_price
                    ])
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => "Achat réussi: {$quantity}x {$item->objet->name}",
                'meta' => [
                    'gold_before' => $character->gold + $totalPrice,
                    'gold_after' => $character->gold,
                    'total_cost' => $totalPrice,
                    'stock_remaining' => $item->fresh()->stock_quantity
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'achat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcule le prix final avec taxes et remises
     */
    private function calculateFinalPrice(BoutiqueItem $item, Boutique $boutique, $character)
    {
        $price = $item->base_price;
        
        if ($boutique->tax_rate > 0) {
            $price *= (1 + $boutique->tax_rate / 100);
        }
        
        if ($boutique->discount_rate > 0) {
            $price *= (1 - $boutique->discount_rate / 100);
        }
        
        return round($price, 2);
    }

    /**
     * Ajoute un objet à l'inventaire
     */
    private function addToInventory($character, $objet, $quantity)
    {
        if ($objet->stackable) {
            $existingItem = InventairePersonnage::where('personnage_id', $character->id)
                ->where('objet_id', $objet->id)
                ->where('is_equipped', false)
                ->first();

            if ($existingItem) {
                $existingItem->update(['quantite' => $existingItem->quantite + $quantity]);
                return;
            }
        }

        InventairePersonnage::create([
            'personnage_id' => $character->id,
            'objet_id' => $objet->id,
            'quantite' => $quantity,
            'is_equipped' => false,
            'durability_current' => $objet->base_durability
        ]);
    }
}