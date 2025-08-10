<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClasseAttribut extends Pivot
{
    protected $table = 'classe_attributs';
    
    protected $fillable = [
        'classe_id',
        'attribut_id', 
        'base_value'
    ];
    
    protected $casts = [
        'base_value' => 'decimal:4'
    ];
    
    public $timestamps = false;
    
    /**
     * Relation vers la classe
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
    
    /**
     * Relation vers l'attribut
     */
    public function attribut(): BelongsTo
    {
        return $this->belongsTo(Attribut::class);
    }
}