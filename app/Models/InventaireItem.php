<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventaireItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventaire_id',
        'objet_id',
        'quantity',
        'durability',
        'is_equipped'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'durability' => 'integer',
        'is_equipped' => 'boolean'
    ];

    /**
     * Relation avec l'inventaire
     */
    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class, 'inventaire_id');
    }

    /**
     * Relation avec l'objet
     */
    public function objet(): BelongsTo
    {
        return $this->belongsTo(Objet::class, 'objet_id');
    }

    /**
     * Scope pour les items équipés
     */
    public function scopeEquipped($query)
    {
        return $query->where('is_equipped', true);
    }

    /**
     * Scope pour les items non équipés
     */
    public function scopeUnequipped($query)
    {
        return $query->where('is_equipped', false);
    }

    /**
     * Vérifie si l'item est cassé (durabilité à 0)
     */
    public function isBroken(): bool
    {
        return $this->durability !== null && $this->durability <= 0;
    }

    /**
     * Vérifie si l'item peut être équipé
     */
    public function canBeEquipped(): bool
    {
        return !$this->is_equipped && 
               $this->objet->isEquippable() && 
               !$this->isBroken();
    }

    /**
     * Initialise la durabilité depuis l'objet
     */
    public function initializeDurability(): void
    {
        if ($this->objet->hasDurability() && $this->durability === null) {
            $this->durability = $this->objet->base_durability;
            $this->save();
        }
    }

    /**
     * Réduit la durabilité
     */
    public function reduceDurability(int $amount = 1): bool
    {
        if ($this->durability === null) {
            return false;
        }

        $this->durability = max(0, $this->durability - $amount);
        $this->save();

        return $this->durability > 0;
    }
}