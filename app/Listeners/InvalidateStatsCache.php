<?php

namespace App\Listeners;

use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Services\CharacterStatsCacheManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateStatsCache implements ShouldQueue
{
    use InteractsWithQueue;

    private CharacterStatsCacheManager $cacheManager;

    public function __construct(CharacterStatsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Gère l'invalidation du cache lors de la mise à jour d'un attribut
     */
    public function handleAttributeUpdated(AttributeUpdated $event): void
    {
        $attribut = $event->attribut;
        $changes = $event->changes;
        
        // Vérifier si les changements impactent les calculs
        $impactingFields = ['default_value', 'min_value', 'max_value', 'type'];
        $hasImpactingChanges = !empty(array_intersect(array_keys($changes), $impactingFields));
        
        if ($hasImpactingChanges) {
            Log::info('Invalidating cache due to impacting attribute changes', [
                'attribut_id' => $attribut->id,
                'changes' => $changes
            ]);
            
            // Invalider le cache pour cet attribut sur tous les personnages
            $this->cacheManager->invalidateAttributeCache(
                $attribut->id, 
                'attribute_updated: ' . implode(', ', array_keys($changes))
            );
            
            // Déclencher un recalcul en batch
            $this->cacheManager->triggerBatchRecalculation(
                $attribut->id, 
                'attribute_updated'
            );
        }
    }

    /**
     * Gère le nettoyage du cache lors de la suppression d'un attribut
     */
    public function handleAttributeDeleted(AttributeDeleted $event): void
    {
        $attributId = $event->attributId;
        
        Log::info('Cleaning up cache for deleted attribute', [
            'attribut_id' => $attributId
        ]);
        
        // Nettoyer le cache pour l'attribut supprimé
        $this->cacheManager->cleanupDeletedAttributeCache($attributId);
    }

    /**
     * Détermine quels événements ce listener doit écouter
     */
    public function subscribe($events): void
    {
        $events->listen(
            AttributeUpdated::class,
            [InvalidateStatsCache::class, 'handleAttributeUpdated']
        );
        
        $events->listen(
            AttributeDeleted::class,
            [InvalidateStatsCache::class, 'handleAttributeDeleted']
        );
    }
}