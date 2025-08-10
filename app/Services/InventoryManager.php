<?php

namespace App\Services;

use App\Models\Personnage;
use App\Models\Objet;
use App\Models\Inventaire;
use App\Models\InventaireItem;
use App\Events\ItemEquipped;
use App\Events\ItemUnequipped;
use App\Events\ItemBroken;
use App\Events\InventoryMerged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryManager
{
    /**
     * Crée un inventaire pour un personnage
     */
    public function createInventory(Personnage $personnage): Inventaire
    {
        return Inventaire::firstOrCreate([
            'personnage_id' => $personnage->id
        ]);
    }

    /**
     * Ajoute un objet à l'inventaire
     */
    public function addItem(Personnage $personnage, Objet $objet, int $quantity = 1): InventaireItem
    {
        return DB::transaction(function () use ($personnage, $objet, $quantity) {
            $inventaire = $this->createInventory($personnage);
            
            // Si l'objet est stackable, essayer de fusionner avec un item existant non équipé
            if ($objet->stackable) {
                $existingItem = $inventaire->items()
                    ->where('objet_id', $objet->id)
                    ->where('is_equipped', false)
                    ->first();
                
                if ($existingItem) {
                    $existingItem->quantity += $quantity;
                    $existingItem->save();
                    
                    event(new InventoryMerged($personnage, $objet, $quantity));
                    return $existingItem;
                }
            }
            
            // Créer un nouvel item
            $item = new InventaireItem([
                'inventaire_id' => $inventaire->id,
                'objet_id' => $objet->id,
                'quantity' => $quantity,
                'is_equipped' => false
            ]);
            
            // Initialiser la durabilité si nécessaire
            if ($objet->hasDurability()) {
                $item->durability = $objet->base_durability;
            }
            
            $item->save();
            return $item;
        });
    }

    /**
     * Équipe un objet
     */
    public function equipItem(Personnage $personnage, InventaireItem $item): bool
    {
        return DB::transaction(function () use ($personnage, $item) {
            // Vérifications
            if (!$item->canBeEquipped()) {
                return false;
            }
            
            $inventaire = $personnage->inventaire;
            if (!$inventaire || $item->inventaire_id !== $inventaire->id) {
                return false;
            }
            
            // Vérifier les limites de slot
            if (!$inventaire->canEquipInSlot($item->objet->slot_id)) {
                return false;
            }
            
            // Si l'item est stacké (quantity > 1), créer un item séparé pour l'équipement
            if ($item->quantity > 1) {
                $item->quantity -= 1;
                $item->save();
                
                $equippedItem = new InventaireItem([
                    'inventaire_id' => $item->inventaire_id,
                    'objet_id' => $item->objet_id,
                    'quantity' => 1,
                    'durability' => $item->durability,
                    'is_equipped' => true
                ]);
                $equippedItem->save();
                
                event(new ItemEquipped($personnage, $equippedItem));
                return true;
            }
            
            // Équiper l'item directement
            $item->is_equipped = true;
            $item->save();
            
            event(new ItemEquipped($personnage, $item));
            return true;
        });
    }

    /**
     * Déséquipe un objet
     */
    public function unequipItem(Personnage $personnage, InventaireItem $item): bool
    {
        return DB::transaction(function () use ($personnage, $item) {
            if (!$item->is_equipped) {
                return false;
            }
            
            $inventaire = $personnage->inventaire;
            if (!$inventaire || $item->inventaire_id !== $inventaire->id) {
                return false;
            }
            
            // Déséquiper
            $item->is_equipped = false;
            $item->save();
            
            // Essayer de fusionner avec un item stackable existant
            if ($item->objet->stackable && $item->quantity === 1) {
                $existingItem = $inventaire->items()
                    ->where('objet_id', $item->objet_id)
                    ->where('is_equipped', false)
                    ->where('id', '!=', $item->id)
                    ->first();
                
                if ($existingItem) {
                    $existingItem->quantity += $item->quantity;
                    $existingItem->save();
                    $item->delete();
                    
                    event(new InventoryMerged($personnage, $item->objet, $item->quantity));
                }
            }
            
            event(new ItemUnequipped($personnage, $item));
            return true;
        });
    }

    /**
     * Gère la casse d'un objet (durabilité à 0)
     */
    public function handleBrokenItem(InventaireItem $item): void
    {
        if ($item->isBroken() && $item->is_equipped) {
            $personnage = $item->inventaire->personnage;
            
            // Auto-déséquipement
            $item->is_equipped = false;
            $item->save();
            
            event(new ItemBroken($personnage, $item));
            event(new ItemUnequipped($personnage, $item));
            
            Log::info("Item {$item->objet->name} broken and auto-unequipped for character {$personnage->name}");
        }
    }

    /**
     * Réduit la durabilité d'un item équipé
     */
    public function reduceDurability(InventaireItem $item, int $amount = 1): void
    {
        if ($item->is_equipped && $item->durability !== null) {
            $stillUsable = $item->reduceDurability($amount);
            
            if (!$stillUsable) {
                $this->handleBrokenItem($item);
            }
        }
    }

    /**
     * Obtient tous les modificateurs d'équipement pour un personnage
     */
    public function getEquipmentModifiers(Personnage $personnage): array
    {
        $inventaire = $personnage->inventaire;
        if (!$inventaire) {
            return [];
        }
        
        $modifiers = [];
        $equippedItems = $inventaire->equippedItems()->with(['objet.attributModifiers.attribut'])->get();
        
        foreach ($equippedItems as $item) {
            foreach ($item->objet->attributModifiers as $modifier) {
                $attributSlug = $modifier->attribut->slug;
                
                if (!isset($modifiers[$attributSlug])) {
                    $modifiers[$attributSlug] = ['flat' => 0, 'percent' => 0];
                }
                
                $modifiers[$attributSlug][$modifier->modifier_type] += $modifier->modifier_value;
            }
        }
        
        return $modifiers;
    }
}