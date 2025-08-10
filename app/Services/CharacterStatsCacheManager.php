<?php

namespace App\Services;

use App\Models\Personnage;
use App\Models\Attribut;
use App\Jobs\RecalculateCharacterStatsBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CharacterStatsCacheManager
{
    private CharacterStatsCalculator $calculator;

    public function __construct(CharacterStatsCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Récupère la valeur finale d'un attribut pour un personnage
     * Utilise le cache si disponible, sinon recalcule
     */
    public function getFinalValue(int $personnageId, int $attributId): float
    {
        // Vérifier le cache d'abord
        $cached = $this->getCachedValue($personnageId, $attributId);
        
        if ($cached !== null && !$cached['needs_recalculation']) {
            return $cached['final_value'];
        }
        
        // Recalculer si nécessaire
        return $this->recalculateAndCache($personnageId, $attributId);
    }

    /**
     * Récupère la valeur finale d'un attribut par son slug
     */
    public function getFinalValueBySlug(int $personnageId, string $attributSlug): ?float
    {
        $attribut = Attribut::where('slug', $attributSlug)->first();
        
        if (!$attribut) {
            return null;
        }
        
        return $this->getFinalValue($personnageId, $attribut->id);
    }

    /**
     * Récupère toutes les statistiques finales d'un personnage
     */
    public function getAllFinalValues(int $personnageId): Collection
    {
        return DB::table('personnage_attributs_cache')
            ->join('attributs', 'attributs.id', '=', 'personnage_attributs_cache.attribut_id')
            ->where('personnage_attributs_cache.personnage_id', $personnageId)
            ->where('attributs.is_visible', true)
            ->select([
                'attributs.id',
                'attributs.name',
                'attributs.slug',
                'attributs.type',
                'personnage_attributs_cache.final_value',
                'personnage_attributs_cache.needs_recalculation',
                'personnage_attributs_cache.calculated_at'
            ])
            ->orderBy('attributs.order')
            ->get()
            ->map(function ($item) use ($personnageId) {
                // Recalculer si nécessaire
                if ($item->needs_recalculation) {
                    $item->final_value = $this->recalculateAndCache($personnageId, $item->id);
                    $item->needs_recalculation = false;
                }
                return $item;
            });
    }

    /**
     * Recalcule et met en cache la valeur d'un attribut pour un personnage
     */
    public function recalculateAndCache(int $personnageId, int $attributId): float
    {
        $personnage = Personnage::find($personnageId);
        $attribut = Attribut::find($attributId);
        
        if (!$personnage || !$attribut) {
            Log::warning('Personnage or Attribut not found for recalculation', [
                'personnage_id' => $personnageId,
                'attribut_id' => $attributId
            ]);
            return 0;
        }
        
        $finalValue = $this->calculator->calculateFinalValue($personnage, $attribut);
        
        // Mettre à jour le cache
        DB::table('personnage_attributs_cache')->upsert(
            [[
                'personnage_id' => $personnageId,
                'attribut_id' => $attributId,
                'final_value' => $finalValue,
                'needs_recalculation' => false,
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['personnage_id', 'attribut_id'],
            ['final_value', 'needs_recalculation', 'calculated_at', 'updated_at']
        );
        
        Log::debug('Cache updated for character stat', [
            'personnage_id' => $personnageId,
            'attribut_id' => $attributId,
            'final_value' => $finalValue
        ]);
        
        return $finalValue;
    }

    /**
     * Invalide le cache pour un personnage et un attribut spécifique
     */
    public function invalidateCache(int $personnageId, int $attributId, string $reason = 'manual'): void
    {
        $this->calculator->invalidateCache($personnageId, $attributId);
        
        Log::info('Cache invalidated for specific character stat', [
            'personnage_id' => $personnageId,
            'attribut_id' => $attributId,
            'reason' => $reason
        ]);
    }

    /**
     * Invalide le cache pour tous les attributs d'un personnage
     */
    public function invalidateAllCache(int $personnageId, string $reason = 'manual'): void
    {
        $this->calculator->invalidateAllCache($personnageId);
        
        Log::info('All cache invalidated for character', [
            'personnage_id' => $personnageId,
            'reason' => $reason
        ]);
    }

    /**
     * Invalide le cache pour un attribut sur tous les personnages
     */
    public function invalidateAttributeCache(int $attributId, string $reason = 'manual'): void
    {
        $this->calculator->invalidateAttributeCache($attributId);
        
        Log::info('Cache invalidated for attribute on all characters', [
            'attribut_id' => $attributId,
            'reason' => $reason
        ]);
    }

    /**
     * Déclenche un recalcul en batch pour un attribut
     */
    public function triggerBatchRecalculation(int $attributId, string $reason = 'manual', int $batchSize = 1000): void
    {
        RecalculateCharacterStatsBatch::dispatch($attributId, $reason, $batchSize);
        
        Log::info('Batch recalculation triggered', [
            'attribut_id' => $attributId,
            'reason' => $reason,
            'batch_size' => $batchSize
        ]);
    }

    /**
     * Déclenche un recalcul immédiat pour un personnage spécifique
     */
    public function triggerImmediateRecalculation(int $personnageId, array $attributIds = []): void
    {
        $personnage = Personnage::find($personnageId);
        
        if (!$personnage) {
            Log::warning('Personnage not found for immediate recalculation', [
                'personnage_id' => $personnageId
            ]);
            return;
        }
        
        // Si aucun attribut spécifié, recalculer tous les attributs visibles
        if (empty($attributIds)) {
            $attributIds = Attribut::where('is_visible', true)->pluck('id')->toArray();
        }
        
        foreach ($attributIds as $attributId) {
            $this->recalculateAndCache($personnageId, $attributId);
        }
        
        Log::info('Immediate recalculation completed', [
            'personnage_id' => $personnageId,
            'attribut_ids' => $attributIds
        ]);
    }

    /**
     * Nettoie le cache pour les attributs supprimés
     */
    public function cleanupDeletedAttributeCache(int $attributId): void
    {
        $deletedCount = DB::table('personnage_attributs_cache')
            ->where('attribut_id', $attributId)
            ->delete();
            
        Log::info('Cleaned up cache for deleted attribute', [
            'attribut_id' => $attributId,
            'deleted_entries' => $deletedCount
        ]);
    }

    /**
     * Nettoie le cache pour les personnages supprimés
     */
    public function cleanupDeletedCharacterCache(int $personnageId): void
    {
        $deletedCount = DB::table('personnage_attributs_cache')
            ->where('personnage_id', $personnageId)
            ->delete();
            
        Log::info('Cleaned up cache for deleted character', [
            'personnage_id' => $personnageId,
            'deleted_entries' => $deletedCount
        ]);
    }

    /**
     * Récupère les statistiques du cache
     */
    public function getCacheStats(): array
    {
        $totalEntries = DB::table('personnage_attributs_cache')->count();
        $needsRecalculation = DB::table('personnage_attributs_cache')
            ->where('needs_recalculation', true)
            ->count();
        $oldestEntry = DB::table('personnage_attributs_cache')
            ->min('calculated_at');
        $newestEntry = DB::table('personnage_attributs_cache')
            ->max('calculated_at');
            
        return [
            'total_entries' => $totalEntries,
            'needs_recalculation' => $needsRecalculation,
            'cache_hit_rate' => $totalEntries > 0 ? (($totalEntries - $needsRecalculation) / $totalEntries) * 100 : 0,
            'oldest_entry' => $oldestEntry,
            'newest_entry' => $newestEntry
        ];
    }

    /**
     * Récupère une valeur du cache sans recalcul
     */
    private function getCachedValue(int $personnageId, int $attributId): ?array
    {
        $cached = DB::table('personnage_attributs_cache')
            ->where('personnage_id', $personnageId)
            ->where('attribut_id', $attributId)
            ->first(['final_value', 'needs_recalculation', 'calculated_at']);
            
        return $cached ? (array) $cached : null;
    }
}