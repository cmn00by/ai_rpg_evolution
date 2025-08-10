<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class InventairePersonnage extends Model
{
    use HasFactory;

    protected $table = 'inventaire_items';

    protected $fillable = [
        'inventaire_id',
        'objet_id',
        'quantity',
        'durability',
        'is_equipped',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'durability' => 'integer',
        'is_equipped' => 'boolean',
    ];

    /**
     * Get the inventaire that owns the item.
     */
    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    /**
     * Get the personnage through inventaire.
     */
    public function personnage(): HasOneThrough
    {
        return $this->hasOneThrough(Personnage::class, Inventaire::class, 'id', 'id', 'inventaire_id', 'personnage_id');
    }

    /**
     * Get the objet that belongs to the inventory item.
     */
    public function objet(): BelongsTo
    {
        return $this->belongsTo(Objet::class);
    }

    /**
     * Scope to get items for a specific personnage.
     */
    public function scopeForPersonnage($query, $personnageId)
    {
        return $query->whereHas('inventaire', function ($q) use ($personnageId) {
            $q->where('personnage_id', $personnageId);
        });
    }

    /**
     * Get the personnage_id attribute through the inventaire relationship.
     */
    public function getPersonnageIdAttribute()
    {
        return $this->inventaire?->personnage_id;
    }
}