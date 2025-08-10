<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\BoutiqueItem;
use App\Models\InventairePersonnage;
use App\Models\AchatHistorique;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PlayerShopController extends Controller
{
    /**
     * Liste des boutiques actives
     */
    public function index()
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        $shops = Boutique::where('is_active', true)
            ->withCount('boutiqueItems')
            ->orderBy('name')
            ->get();

        return view('player.shops.index', compact('shops', 'activeCharacter'));
    }

    /**
     * Affiche le catalogue d'une boutique
     */
    public function show(Request $request, Boutique $boutique)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        if (!$boutique->is_active) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        // Vérifier les limites quotidiennes
        $dailyPurchases = $this->getDailyPurchases($activeCharacter, $boutique);
        $canPurchase = $dailyPurchases < $boutique->max_daily_purchases;

        $query = BoutiqueItem::where('boutique_id', $boutique->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with(['objet.rarete', 'objet.slot']);

        // Filtres
        if ($request->filled('rarity')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('rarete_objet_id', $request->rarity);
            });
        }

        if ($request->filled('slot')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('slot_equipement_id', $request->slot);
            });
        }

        if ($request->filled('search')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        $items = $query->orderBy('base_price')
            ->paginate(20);

        // Calculer les prix finaux avec taxes et remises
        $items->getCollection()->transform(function ($item) use ($boutique, $activeCharacter) {
            $item->final_price = $this->calculateFinalPrice($item, $boutique, $activeCharacter);
            return $item;
        });

        // Données pour les filtres
        $rarities = RareteObjet::orderBy('order')->get();
        $slots = SlotEquipement::orderBy('name')->get();

        return view('player.shops.show', compact(
            'boutique',
            'items',
            'activeCharacter',
            'rarities',
            'slots',
            'dailyPurchases',
            'canPurchase'
        ));
    }

    /**
     * Achète un objet
     */
    public function buy(Request $request, Boutique $boutique, BoutiqueItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99'
        ]);

        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            throw ValidationException::withMessages([
                'character' => 'Aucun personnage actif sélectionné.'
            ]);
        }

        // Vérifications de base
        if (!$boutique->is_active) {
            throw ValidationException::withMessages([
                'shop' => 'Cette boutique n\'est pas disponible.'
            ]);
        }

        if (!$item->is_active || $item->boutique_id !== $boutique->id) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet n\'est pas disponible dans cette boutique.'
            ]);
        }

        $quantity = $request->quantity;

        // Vérifier le stock
        if ($item->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stock insuffisant. Stock disponible: {$item->stock_quantity}"
            ]);
        }

        // Vérifier les limites quotidiennes
        $dailyPurchases = $this->getDailyPurchases($activeCharacter, $boutique);
        if ($dailyPurchases >= $boutique->max_daily_purchases) {
            throw ValidationException::withMessages([
                'daily_limit' => 'Vous avez atteint votre limite d\'achats quotidiens dans cette boutique.'
            ]);
        }

        // Calculer le prix final
        $unitPrice = $this->calculateFinalPrice($item, $boutique, $activeCharacter);
        $totalPrice = $unitPrice * $quantity;

        // Vérifier les fonds
        if ($activeCharacter->gold < $totalPrice) {
            throw ValidationException::withMessages([
                'gold' => 'Vous n\'avez pas assez d\'or pour cet achat.'
            ]);
        }

        DB::transaction(function () use ($activeCharacter, $boutique, $item, $quantity, $unitPrice, $totalPrice) {
            $goldBefore = $activeCharacter->gold;
            
            // Déduire l'or
            $activeCharacter->update([
                'gold' => $activeCharacter->gold - $totalPrice
            ]);

            // Réduire le stock
            $item->update([
                'stock_quantity' => $item->stock_quantity - $quantity
            ]);

            // Ajouter à l'inventaire
            $this->addToInventory($activeCharacter, $item->objet, $quantity);

            // Enregistrer l'historique
            AchatHistorique::create([
                'personnage_id' => $activeCharacter->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $item->objet_id,
                'quantite' => $quantity,
                'prix_unitaire' => $unitPrice,
                'prix_total' => $totalPrice,
                'type_transaction' => 'achat',
                'meta_json' => json_encode([
                    'gold_before' => $goldBefore,
                    'gold_after' => $activeCharacter->gold,
                    'tax_rate' => $boutique->tax_rate,
                    'discount_rate' => $boutique->discount_rate,
                    'base_price' => $item->base_price
                ])
            ]);
        });

        return redirect()->back()
            ->with('success', "Achat réussi: {$quantity}x {$item->objet->name} pour {$totalPrice} or.");
    }

    /**
     * Vend un objet à une boutique
     */
    public function sell(Request $request, Boutique $boutique)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventaire_personnages,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            throw ValidationException::withMessages([
                'character' => 'Aucun personnage actif sélectionné.'
            ]);
        }

        $inventoryItem = InventairePersonnage::where('id', $request->inventory_item_id)
            ->where('personnage_id', $activeCharacter->id)
            ->with('objet')
            ->firstOrFail();

        $quantity = $request->quantity;

        // Vérifications
        if ($inventoryItem->is_equipped) {
            throw ValidationException::withMessages([
                'item' => 'Vous ne pouvez pas vendre un objet équipé.'
            ]);
        }

        if ($inventoryItem->quantite < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Quantité insuffisante. Vous avez: {$inventoryItem->quantite}"
            ]);
        }

        // Calculer le prix de vente (généralement moins que le prix d'achat)
        $sellPrice = $inventoryItem->objet->sell_price ?? ($inventoryItem->objet->buy_price * 0.5);
        $totalPrice = $sellPrice * $quantity;

        // Appliquer les modificateurs de la boutique (remise devient bonus)
        if ($boutique->discount_rate > 0) {
            $totalPrice *= (1 + $boutique->discount_rate / 100);
        }

        DB::transaction(function () use ($activeCharacter, $boutique, $inventoryItem, $quantity, $sellPrice, $totalPrice) {
            $goldBefore = $activeCharacter->gold;
            
            // Ajouter l'or
            $activeCharacter->update([
                'gold' => $activeCharacter->gold + $totalPrice
            ]);

            // Réduire la quantité ou supprimer l'objet
            if ($inventoryItem->quantite > $quantity) {
                $inventoryItem->update([
                    'quantite' => $inventoryItem->quantite - $quantity
                ]);
            } else {
                $inventoryItem->delete();
            }

            // Enregistrer l'historique
            AchatHistorique::create([
                'personnage_id' => $activeCharacter->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $inventoryItem->objet_id,
                'quantite' => $quantity,
                'prix_unitaire' => $sellPrice,
                'prix_total' => $totalPrice,
                'type_transaction' => 'vente',
                'meta_json' => json_encode([
                    'gold_before' => $goldBefore,
                    'gold_after' => $activeCharacter->gold,
                    'base_sell_price' => $sellPrice,
                    'shop_bonus' => $boutique->discount_rate
                ])
            ]);
        });

        return redirect()->back()
            ->with('success', "Vente réussie: {$quantity}x {$inventoryItem->objet->name} pour {$totalPrice} or.");
    }

    /**
     * Calcule le prix final avec taxes et remises
     */
    private function calculateFinalPrice(BoutiqueItem $item, Boutique $boutique, $character)
    {
        $price = $item->base_price;
        
        // Appliquer les taxes
        if ($boutique->tax_rate > 0) {
            $price *= (1 + $boutique->tax_rate / 100);
        }
        
        // Appliquer les remises (basées sur la réputation par exemple)
        if ($boutique->discount_rate > 0) {
            $price *= (1 - $boutique->discount_rate / 100);
        }
        
        return round($price, 2);
    }

    /**
     * Récupère le nombre d'achats quotidiens
     */
    private function getDailyPurchases($character, $boutique)
    {
        return AchatHistorique::where('personnage_id', $character->id)
            ->where('boutique_id', $boutique->id)
            ->where('type_transaction', 'achat')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Ajoute un objet à l'inventaire
     */
    private function addToInventory($character, $objet, $quantity)
    {
        if ($objet->stackable) {
            // Essayer de fusionner avec un stack existant
            $existingItem = InventairePersonnage::where('personnage_id', $character->id)
                ->where('objet_id', $objet->id)
                ->where('is_equipped', false)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantite' => $existingItem->quantite + $quantity
                ]);
                return;
            }
        }

        // Créer une nouvelle entrée
        InventairePersonnage::create([
            'inventaire_id' => $character->inventaire->id,
            'personnage_id' => $character->id,
            'objet_id' => $objet->id,
            'quantite' => $quantity,
            'is_equipped' => false,
            'durability_current' => $objet->base_durability
        ]);
    }
}