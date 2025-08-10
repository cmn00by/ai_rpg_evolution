<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Objet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'rarete_id',
        'slot_id',
        'stackable',
        'base_durability',
        'buy_price',
        'sell_price'
    ];

    protected $casts = [
        'stackable' => 'boolean',
        'base_durability' => 'integer',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2'
    ];

    /**
     * Relation avec la rareté
     */
    public function rarete(): BelongsTo
    {
        return $this->belongsTo(RareteObjet::class, 'rarete_id');
    }

    /**
     * Relation avec le slot d'équipement
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(SlotEquipement::class, 'slot_id');
    }

    /**
     * Relation avec les modificateurs d'attributs
     */
    public function attributModifiers(): HasMany
    {
        return $this->hasMany(ObjetAttribut::class, 'objet_id');
    }

    /**
     * Relation avec les items d'inventaire
     */
    public function inventaireItems(): HasMany
    {
        return $this->hasMany(InventaireItem::class, 'objet_id');
    }

    /**
     * Vérifie si l'objet peut être équipé
     */
    public function isEquippable(): bool
    {
        return !is_null($this->slot_id);
    }

    /**
     * Vérifie si l'objet a une durabilité
     */
    public function hasDurability(): bool
    {
        return !is_null($this->base_durability);
    }
}