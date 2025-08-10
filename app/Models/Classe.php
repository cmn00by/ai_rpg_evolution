<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $fillable = ['name','slug','base_level'];

    protected $casts = ['base_level' => 'integer'];

    public function personnages()
    {
        return $this->hasMany(Personnage::class);
    }

    public function attributs()
    {
        return $this->belongsToMany(Attribut::class, 'classe_attributs')
            ->withPivot('base_value');
    }
}