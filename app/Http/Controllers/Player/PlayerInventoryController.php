<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\InventairePersonnage;
use App\Models\Objet;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayerInventoryController extends Controller
{
    /**
     * Affiche l'inventaire du joueur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        $query = InventairePersonnage::where('personnage_id', $activeCharacter->id)
            ->with(['objet.rarete', 'objet.slot', 'objet.objetAttributs.attribut']);

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

        if ($request->filled('equipped')) {
            $query->where('is_equipped', $request->equipped === 'true');
        }

        if ($request->filled('search')) {
            $query->whereHas('objet', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $inventory = $query->orderBy('is_equipped', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Données pour les filtres
        $rarities = RareteObjet::orderBy('order')->get();
        $slots = SlotEquipement::orderBy('name')->get();

        return view('player.inventory.index', compact(
            'inventory',
            'activeCharacter',
            'rarities',
            'slots'
        ));
    }

    /**
     * Équipe un objet
     */
    public function equip(Request $request, InventairePersonnage $item)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        // Vérifier que l'objet appartient au personnage actif
        if ($item->personnage_id !== $activeCharacter->id) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet ne vous appartient pas.'
            ]);
        }

        // Vérifier que l'objet n'est pas déjà équipé
        if ($item->is_equipped) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet est déjà équipé.'
            ]);
        }

        // Vérifier la durabilité
        if ($item->durability_current !== null && $item->durability_current <= 0) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet est cassé et ne peut pas être équipé.'
            ]);
        }

        DB::transaction(function () use ($item, $activeCharacter) {
            $slot = $item->objet->slot;
            
            if ($slot) {
                // Vérifier les limites de slot
                $currentEquipped = InventairePersonnage::where('personnage_id', $activeCharacter->id)
                    ->where('is_equipped', true)
                    ->whereHas('objet', function ($q) use ($slot) {
                        $q->where('slot_equipement_id', $slot->id);
                    })
                    ->count();

                if ($currentEquipped >= $slot->max_per_slot) {
                    throw ValidationException::withMessages([
                        'item' => "Vous avez déjà atteint la limite pour ce type d'équipement ({$slot->max_per_slot})."
                    ]);
                }
            }

            // Si l'objet est stackable et qu'on en équipe un, on doit le séparer
            if ($item->objet->stackable && $item->quantite > 1) {
                // Créer une nouvelle entrée pour l'objet équipé
                InventairePersonnage::create([
                    'personnage_id' => $activeCharacter->id,
                    'objet_id' => $item->objet_id,
                    'quantite' => 1,
                    'is_equipped' => true,
                    'durability_current' => $item->durability_current
                ]);

                // Réduire la quantité de l'objet original
                $item->update(['quantite' => $item->quantite - 1]);
            } else {
                // Équiper directement
                $item->update(['is_equipped' => true]);
            }

            // Invalider le cache des stats
            $activeCharacter->invalidateStatsCache();
        });

        return redirect()->back()
            ->with('success', "Objet {$item->objet->name} équipé avec succès.");
    }

    /**
     * Déséquipe un objet
     */
    public function unequip(Request $request, InventairePersonnage $item)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        // Vérifier que l'objet appartient au personnage actif
        if ($item->personnage_id !== $activeCharacter->id) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet ne vous appartient pas.'
            ]);
        }

        // Vérifier que l'objet est équipé
        if (!$item->is_equipped) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet n\'est pas équipé.'
            ]);
        }

        DB::transaction(function () use ($item, $activeCharacter) {
            // Si l'objet est stackable, essayer de le fusionner avec un stack existant
            if ($item->objet->stackable) {
                $existingStack = InventairePersonnage::where('personnage_id', $activeCharacter->id)
                    ->where('objet_id', $item->objet_id)
                    ->where('is_equipped', false)
                    ->where('id', '!=', $item->id)
                    ->first();

                if ($existingStack) {
                    // Fusionner avec le stack existant
                    $existingStack->update([
                        'quantite' => $existingStack->quantite + $item->quantite
                    ]);
                    
                    // Supprimer l'objet équipé
                    $item->delete();
                } else {
                    // Déséquiper simplement
                    $item->update(['is_equipped' => false]);
                }
            } else {
                // Déséquiper simplement
                $item->update(['is_equipped' => false]);
            }

            // Invalider le cache des stats
            $activeCharacter->invalidateStatsCache();
        });

        return redirect()->back()
            ->with('success', "Objet {$item->objet->name} déséquipé avec succès.");
    }

    /**
     * Répare un objet
     */
    public function repair(Request $request, InventairePersonnage $item)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        // Vérifier que l'objet appartient au personnage actif
        if ($item->personnage_id !== $activeCharacter->id) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet ne vous appartient pas.'
            ]);
        }

        // Vérifier que l'objet a une durabilité
        if ($item->objet->base_durability === null) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet ne peut pas être réparé.'
            ]);
        }

        // Vérifier que l'objet a besoin d'être réparé
        if ($item->durability_current >= $item->objet->base_durability) {
            throw ValidationException::withMessages([
                'item' => 'Cet objet n\'a pas besoin d\'être réparé.'
            ]);
        }

        // Calculer le coût de réparation (exemple: 10% de la valeur d'achat par point de durabilité)
        $repairCost = ($item->objet->base_durability - $item->durability_current) * 
                     ($item->objet->buy_price * 0.1);

        // Vérifier que le joueur a assez d'or
        if ($activeCharacter->gold < $repairCost) {
            throw ValidationException::withMessages([
                'gold' => 'Vous n\'avez pas assez d\'or pour réparer cet objet.'
            ]);
        }

        DB::transaction(function () use ($item, $activeCharacter, $repairCost) {
            // Déduire l'or
            $activeCharacter->update([
                'gold' => $activeCharacter->gold - $repairCost
            ]);

            // Réparer l'objet
            $item->update([
                'durability_current' => $item->objet->base_durability
            ]);
        });

        return redirect()->back()
            ->with('success', "Objet {$item->objet->name} réparé pour {$repairCost} or.");
    }

    /**
     * Affiche les détails d'un objet
     */
    public function show(InventairePersonnage $item)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        // Vérifier que l'objet appartient au personnage actif
        if ($item->personnage_id !== $activeCharacter->id) {
            abort(403, 'Cet objet ne vous appartient pas.');
        }

        $item->load(['objet.rarete', 'objet.slot', 'objet.objetAttributs.attribut']);

        return view('player.inventory.show', compact('item', 'activeCharacter'));
    }
}