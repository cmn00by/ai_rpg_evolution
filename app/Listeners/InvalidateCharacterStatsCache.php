<?php

namespace App\Listeners;

use App\Services\CharacterStatsCacheManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateCharacterStatsCache implements ShouldQueue
{
    use InteractsWithQueue;

    private CharacterStatsCacheManager $cacheManager;

    public function __construct(CharacterStatsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Gère l'invalidation du cache lors du changement de classe d'un personnage
     */
    public function handleCharacterClassChanged($event): void
    {
        $personnageId = $event->personnageId ?? $event->personnage->id ?? null;
        $oldClasseId = $event->oldClasseId ?? null;
        $newClasseId = $event->newClasseId ?? $event->personnage->classe_id ?? null;
        
        if (!$personnageId) {
            Log::warning('No personnage ID found in character class change event');
            return;
        }
        
        Log::info('Invalidating cache due to character class change', [
            'personnage_id' => $personnageId,
            'old_classe_id' => $oldClasseId,
            'new_classe_id' => $newClasseId
        ]);
        
        // Invalider tout le cache du personnage car la classe affecte tous les attributs
        $this->cacheManager->invalidateAllCache(
            $personnageId, 
            'class_changed'
        );
        
        // Déclencher un recalcul immédiat pour ce personnage
        $this->cacheManager->triggerImmediateRecalculation($personnageId);
    }

    /**
     * Gère l'invalidation du cache lors de la modification d'un pivot classe_attributs
     */
    public function handleClassAttributePivotChanged($event): void
    {
        $classeId = $event->classeId ?? null;
        $attributId = $event->attributId ?? null;
        
        if (!$classeId || !$attributId) {
            Log::warning('Missing classe_id or attribut_id in class attribute pivot change event');
            return;
        }
        
        Log::info('Invalidating cache due to class attribute pivot change', [
            'classe_id' => $classeId,
            'attribut_id' => $attributId
        ]);
        
        // Invalider le cache pour cet attribut sur tous les personnages de cette classe
        $this->invalidateCacheForClassAttribute($classeId, $attributId);
    }

    /**
     * Gère l'invalidation du cache lors de la modification d'un pivot personnage_attributs
     */
    public function handlePersonnageAttributePivotChanged($event): void
    {
        $personnageId = $event->personnageId ?? null;
        $attributId = $event->attributId ?? null;
        
        if (!$personnageId || !$attributId) {
            Log::warning('Missing personnage_id or attribut_id in personnage attribute pivot change event');
            return;
        }
        
        Log::info('Invalidating cache due to personnage attribute pivot change', [
            'personnage_id' => $personnageId,
            'attribut_id' => $attributId
        ]);
        
        // Invalider le cache pour cet attribut spécifique de ce personnage
        $this->cacheManager->invalidateCache(
            $personnageId, 
            $attributId, 
            'personnage_attribute_pivot_changed'
        );
        
        // Recalculer immédiatement
        $this->cacheManager->recalculateAndCache($personnageId, $attributId);
        
        // Si c'est un attribut dérivé, invalider aussi les attributs qui en dépendent
        $this->invalidateDependentAttributes($personnageId, $attributId);
    }

    /**
     * Gère l'invalidation du cache lors de l'équipement/déséquipement d'objets
     * TODO: À implémenter quand les tables d'équipement seront créées
     */
    public function handleEquipmentChanged($event): void
    {
        $personnageId = $event->personnageId ?? null;
        $affectedAttributes = $event->affectedAttributes ?? [];
        
        if (!$personnageId) {
            Log::warning('No personnage ID found in equipment change event');
            return;
        }
        
        Log::info('Invalidating cache due to equipment change', [
            'personnage_id' => $personnageId,
            'affected_attributes' => $affectedAttributes
        ]);
        
        if (empty($affectedAttributes)) {
            // Si on ne sait pas quels attributs sont affectés, invalider tout
            $this->cacheManager->invalidateAllCache(
                $personnageId, 
                'equipment_changed'
            );
            $this->cacheManager->triggerImmediateRecalculation($personnageId);
        } else {
            // Invalider seulement les attributs affectés
            foreach ($affectedAttributes as $attributId) {
                $this->cacheManager->invalidateCache(
                    $personnageId, 
                    $attributId, 
                    'equipment_changed'
                );
                $this->cacheManager->recalculateAndCache($personnageId, $attributId);
            }
        }
    }

    /**
     * Gère l'invalidation du cache lors de l'ajout/suppression d'effets temporaires
     * TODO: À implémenter quand la table character_effects sera créée
     */
    public function handleTemporaryEffectChanged($event): void
    {
        $personnageId = $event->personnageId ?? null;
        $affectedAttributes = $event->affectedAttributes ?? [];
        
        if (!$personnageId) {
            Log::warning('No personnage ID found in temporary effect change event');
            return;
        }
        
        Log::info('Invalidating cache due to temporary effect change', [
            'personnage_id' => $personnageId,
            'affected_attributes' => $affectedAttributes
        ]);
        
        if (empty($affectedAttributes)) {
            // Si on ne sait pas quels attributs sont affectés, invalider tout
            $this->cacheManager->invalidateAllCache(
                $personnageId, 
                'temporary_effect_changed'
            );
            $this->cacheManager->triggerImmediateRecalculation($personnageId);
        } else {
            // Invalider seulement les attributs affectés
            foreach ($affectedAttributes as $attributId) {
                $this->cacheManager->invalidateCache(
                    $personnageId, 
                    $attributId, 
                    'temporary_effect_changed'
                );
                $this->cacheManager->recalculateAndCache($personnageId, $attributId);
            }
        }
    }

    /**
     * Invalide le cache pour un attribut sur tous les personnages d'une classe
     */
    private function invalidateCacheForClassAttribute(int $classeId, int $attributId): void
    {
        // Récupérer tous les personnages de cette classe
        $personnageIds = \App\Models\Personnage::where('classe_id', $classeId)
            ->pluck('id')
            ->toArray();
        
        foreach ($personnageIds as $personnageId) {
            $this->cacheManager->invalidateCache(
                $personnageId, 
                $attributId, 
                'class_attribute_pivot_changed'
            );
        }
        
        // Déclencher un recalcul en batch pour cet attribut
        $this->cacheManager->triggerBatchRecalculation(
            $attributId, 
            'class_attribute_pivot_changed'
        );
    }

    /**
     * Invalide les attributs dérivés qui dépendent de l'attribut modifié
     */
    private function invalidateDependentAttributes(int $personnageId, int $attributId): void
    {
        // Récupérer l'attribut pour vérifier son slug
        $attribut = \App\Models\Attribut::find($attributId);
        
        if (!$attribut) {
            return;
        }
        
        // Définir les dépendances (à adapter selon les besoins)
        $dependencies = [
            'force' => ['pv-max'], // PV max dépend de la force
            'vigueur' => ['pv-max'], // PV max dépend aussi de la vigueur
            // Ajouter d'autres dépendances ici
        ];
        
        if (isset($dependencies[$attribut->slug])) {
            foreach ($dependencies[$attribut->slug] as $dependentSlug) {
                $dependentAttribut = \App\Models\Attribut::where('slug', $dependentSlug)->first();
                
                if ($dependentAttribut) {
                    $this->cacheManager->invalidateCache(
                        $personnageId, 
                        $dependentAttribut->id, 
                        'dependent_attribute_changed'
                    );
                    $this->cacheManager->recalculateAndCache($personnageId, $dependentAttribut->id);
                }
            }
        }
    }
}