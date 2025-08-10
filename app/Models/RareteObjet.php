<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RareteObjet extends Model
{
    use HasFactory;

    protected $table = 'raretes_objets';

    protected $fillable = [
        'name',
        'slug',
        'order',
        'color_hex',
        'multiplier'
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'order' => 'integer'
    ];

    /**
     * Relation avec les objets
     */
    public function objets(): HasMany
    {
        return $this->hasMany(Objet::class, 'rarete_id');
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}