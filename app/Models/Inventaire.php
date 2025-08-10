<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnage_id'
    ];

    /**
     * Relation avec le personnage (1-1)
     */
    public function personnage(): BelongsTo
    {
        return $this->belongsTo(Personnage::class, 'personnage_id');
    }

    /**
     * Relation avec les items d'inventaire
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventaireItem::class, 'inventaire_id');
    }

    /**
     * Items équipés uniquement
     */
    public function equippedItems(): HasMany
    {
        return $this->hasMany(InventaireItem::class, 'inventaire_id')
                    ->where('is_equipped', true);
    }

    /**
     * Items non équipés uniquement
     */
    public function unequippedItems(): HasMany
    {
        return $this->hasMany(InventaireItem::class, 'inventaire_id')
                    ->where('is_equipped', false);
    }

    /**
     * Obtient tous les modificateurs d'attributs des objets équipés
     */
    public function getEquippedModifiers()
    {
        return $this->equippedItems()
                    ->with(['objet.attributModifiers.attribut'])
                    ->get()
                    ->flatMap(function ($item) {
                        return $item->objet->attributModifiers;
                    });
    }

    /**
     * Vérifie si un slot peut accueillir un nouvel équipement
     */
    public function canEquipInSlot($slotId): bool
    {
        $slot = SlotEquipement::find($slotId);
        if (!$slot) {
            return false;
        }

        $currentEquipped = $this->equippedItems()
                               ->whereHas('objet', function ($query) use ($slotId) {
                                   $query->where('slot_id', $slotId);
                               })
                               ->count();

        return $currentEquipped < $slot->max_per_slot;
    }
}