<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlotEquipement extends Model
{
    use HasFactory;

    protected $table = 'slots_equipement';

    protected $fillable = [
        'name',
        'slug',
        'max_per_slot'
    ];

    protected $casts = [
        'max_per_slot' => 'integer'
    ];

    /**
     * Relation avec les objets
     */
    public function objets(): HasMany
    {
        return $this->hasMany(Objet::class, 'slot_id');
    }
}