<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjetAttribut extends Model
{
    use HasFactory;

    protected $table = 'objet_attributs';

    protected $fillable = [
        'objet_id',
        'attribut_id',
        'modifier_type',
        'modifier_value'
    ];

    protected $casts = [
        'modifier_value' => 'decimal:2'
    ];

    // Clé primaire composite
    protected $primaryKey = ['objet_id', 'attribut_id', 'modifier_type'];
    public $incrementing = false;

    /**
     * Relation avec l'objet
     */
    public function objet(): BelongsTo
    {
        return $this->belongsTo(Objet::class, 'objet_id');
    }

    /**
     * Relation avec l'attribut
     */
    public function attribut(): BelongsTo
    {
        return $this->belongsTo(Attribut::class, 'attribut_id');
    }

    /**
     * Scope pour les modificateurs flat
     */
    public function scopeFlat($query)
    {
        return $query->where('modifier_type', 'flat');
    }

    /**
     * Scope pour les modificateurs percent
     */
    public function scopePercent($query)
    {
        return $query->where('modifier_type', 'percent');
    }

    /**
     * Vérifie si c'est un modificateur flat
     */
    public function isFlat(): bool
    {
        return $this->modifier_type === 'flat';
    }

    /**
     * Vérifie si c'est un modificateur percent
     */
    public function isPercent(): bool
    {
        return $this->modifier_type === 'percent';
    }
}