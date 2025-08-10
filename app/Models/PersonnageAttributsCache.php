<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnageAttributsCache extends Model
{
    protected $table = 'personnage_attributs_cache';
    
    // Pas d'auto-increment car on utilise une clé composite
    public $incrementing = false;
    
    // Clé primaire composite
    protected $primaryKey = ['personnage_id', 'attribut_id'];
    
    protected $fillable = [
        'personnage_id',
        'attribut_id', 
        'final_value',
        'needs_recalculation',
        'calculated_at'
    ];
    
    protected $casts = [
        'personnage_id' => 'integer',
        'attribut_id' => 'integer',
        'final_value' => 'decimal:4',
        'needs_recalculation' => 'boolean',
        'calculated_at' => 'datetime'
    ];
    
    /**
     * Relation vers le personnage
     */
    public function personnage(): BelongsTo
    {
        return $this->belongsTo(Personnage::class);
    }
    
    /**
     * Relation vers l'attribut
     */
    public function attribut(): BelongsTo
    {
        return $this->belongsTo(Attribut::class);
    }
    
    /**
     * Scope pour récupérer les entrées qui nécessitent un recalcul
     */
    public function scopeNeedsRecalculation($query)
    {
        return $query->where('needs_recalculation', true);
    }
    
    /**
     * Scope pour récupérer les entrées d'un personnage spécifique
     */
    public function scopeForPersonnage($query, int $personnageId)
    {
        return $query->where('personnage_id', $personnageId);
    }
    
    /**
     * Scope pour récupérer les entrées d'un attribut spécifique
     */
    public function scopeForAttribut($query, int $attributId)
    {
        return $query->where('attribut_id', $attributId);
    }
    
    /**
     * Scope pour récupérer les entrées avec les attributs visibles
     */
    public function scopeWithVisibleAttributes($query)
    {
        return $query->join('attributs', 'attributs.id', '=', 'personnage_attributs_cache.attribut_id')
                    ->where('attributs.is_visible', true);
    }
    
    /**
     * Marque cette entrée comme nécessitant un recalcul
     */
    public function markForRecalculation(): void
    {
        $this->update([
            'needs_recalculation' => true,
            'updated_at' => now()
        ]);
    }
    
    /**
     * Met à jour la valeur finale et marque comme calculé
     */
    public function updateFinalValue(float $value): void
    {
        $this->update([
            'final_value' => $value,
            'needs_recalculation' => false,
            'calculated_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    /**
     * Vérifie si l'entrée est obsolète (plus ancienne que X minutes)
     */
    public function isStale(int $minutes = 60): bool
    {
        if (!$this->calculated_at) {
            return true;
        }
        
        return $this->calculated_at->diffInMinutes(now()) > $minutes;
    }
    
    /**
     * Récupère toutes les entrées obsolètes
     */
    public static function getStaleEntries(int $minutes = 60)
    {
        return static::where('calculated_at', '<', now()->subMinutes($minutes))
                    ->orWhereNull('calculated_at');
    }
    
    /**
     * Nettoie les entrées pour les attributs supprimés
     */
    public static function cleanupDeletedAttributes(): int
    {
        return static::whereNotExists(function ($query) {
            $query->select('id')
                  ->from('attributs')
                  ->whereColumn('attributs.id', 'personnage_attributs_cache.attribut_id');
        })->delete();
    }
    
    /**
     * Nettoie les entrées pour les personnages supprimés
     */
    public static function cleanupDeletedPersonnages(): int
    {
        return static::whereNotExists(function ($query) {
            $query->select('id')
                  ->from('personnages')
                  ->whereColumn('personnages.id', 'personnage_attributs_cache.personnage_id');
        })->delete();
    }
    
    /**
     * Statistiques du cache
     */
    public static function getCacheStatistics(): array
    {
        $total = static::count();
        $needsRecalc = static::needsRecalculation()->count();
        $stale = static::getStaleEntries()->count();
        
        return [
            'total_entries' => $total,
            'needs_recalculation' => $needsRecalc,
            'stale_entries' => $stale,
            'cache_hit_rate' => $total > 0 ? (($total - $needsRecalc) / $total) * 100 : 0,
            'freshness_rate' => $total > 0 ? (($total - $stale) / $total) * 100 : 0
        ];
    }
}