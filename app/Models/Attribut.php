<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribut extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attributs';

    protected $fillable = [
        'name','slug','type','default_value','min_value','max_value','is_visible','order',
    ];

    protected $casts = [
        'default_value' => 'float',
        'min_value'     => 'float',
        'max_value'     => 'float',
        'is_visible'    => 'bool',
        'order'         => 'integer',
    ];

    // Relations
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'classe_attributs')
            ->withPivot('base_value')
            ->using(ClasseAttribut::class);
    }

    public function personnages()
    {
        return $this->belongsToMany(Personnage::class, 'personnage_attributs')
            ->withPivot('value');
    }
}