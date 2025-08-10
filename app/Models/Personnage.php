<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\User;
use App\Models\Classe;
use App\Models\Attribut;

class Personnage extends Model
{
    use HasFactory;
    protected $table = 'personnages';

    protected $fillable = ['user_id','classe_id','name','level','gold','reputation','is_active'];

    protected $casts = [
        'user_id'  => 'integer',
        'classe_id'=> 'integer',
        'level'    => 'integer',
        'gold'     => 'integer',
        'reputation' => 'integer',
        'is_active' => 'boolean',
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

    public function inventaireItems(): HasManyThrough
    {
        return $this->hasManyThrough(InventaireItem::class, Inventaire::class, 'personnage_id', 'inventaire_id');
    }

    public function achatHistoriques(): HasMany
    {
        return $this->hasMany(AchatHistorique::class);
    }
}