<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Classe;
use App\Models\Attribut;

class Personnage extends Model
{
    use HasFactory;
    protected $table = 'personnages';

    protected $fillable = ['user_id','classe_id','name','level','gold'];

    protected $casts = [
        'user_id'  => 'integer',
        'classe_id'=> 'integer',
        'level'    => 'integer',
        'gold'     => 'integer',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function classe(){ return $this->belongsTo(Classe::class); }

    public function attributs()
    {
        return $this->belongsToMany(Attribut::class, 'personnage_attributs')
            ->withPivot('value');
    }

    /**
     * Relation avec l'inventaire (1-1)
     */
    public function inventaire()
    {
        return $this->hasOne(Inventaire::class, 'personnage_id');
    }
}