<?php

namespace App\Services;

use App\Models\Personnage;
use App\Models\Attribut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CharacterStatsCalculator
{
    /**
     * Calcule la valeur finale d'un attribut pour un personnage
     * selon la formule : base classe + override perso + équipements + effets temporaires
     */
    public function calculateFinalValue(Personnage $personnage, Attribut $attribut): float
    {
        Log::debug('Calculating final value', [
            'personnage_id' => $personnage->id,
            'attribut_id' => $attribut->id,
            'attribut_type' => $attribut->type
        ]);

        switch ($attribut->type) {
            case 'int':
            case 'float':
                return $this->calculateBasicValue($personnage, $attribut);
                
            case 'derived':
                return $this->calculateDerivedValue($personnage, $attribut);
                
            case 'computed_cached':
                return $this->calculateComputedValue($personnage, $attribut);
                
            default:
                return 0;
        }
    }

    /**
     * Calcule la valeur d'un attribut de base (int/float)
     * Formule : (base_classe + override_perso + flat_equipements) * (1 + %_equipements/100) + flat_buffs * (1 + %_buffs/100)
     */
    private function calculateBasicValue(Personnage $personnage, Attribut $attribut): float
    {
        // 1. Base de classe
        $baseValue = $this->getClassBaseValue($personnage, $attribut);
        
        // 2. Override personnage
        $personalOverride = $this->getPersonalOverride($personnage, $attribut);
        
        // 3. Équipements
        $equipmentFlat = $this->getEquipmentFlatBonus($personnage, $attribut);
        $equipmentPercent = $this->getEquipmentPercentBonus($personnage, $attribut);
        
        // 4. Effets temporaires
        $buffsFlat = $this->getTemporaryEffectsFlat($personnage, $attribut);
        $buffsPercent = $this->getTemporaryEffectsPercent($personnage, $attribut);
        
        // Application de la formule
        $raw = $baseValue + $personalOverride + $equipmentFlat;
        $withEquipmentPercent = $raw * (1 + $equipmentPercent / 100);
        $withBuffsFlat = $withEquipmentPercent + $buffsFlat;
        $finalValue = $withBuffsFlat * (1 + $buffsPercent / 100);
        
        // Arrondi selon le type
        if ($attribut->type === 'int') {
            $finalValue = floor($finalValue);
        }
        
        // Application des bornes min/max
        $finalValue = $this->applyBounds($finalValue, $attribut);
        
        Log::debug('Basic value calculation breakdown', [
            'base_value' => $baseValue,
            'personal_override' => $personalOverride,
            'equipment_flat' => $equipmentFlat,
            'equipment_percent' => $equipmentPercent,
            'buffs_flat' => $buffsFlat,
            'buffs_percent' => $buffsPercent,
            'final_value' => $finalValue
        ]);
        
        return $finalValue;
    }

    /**
     * Calcule la valeur d'un attribut dérivé
     */
    private function calculateDerivedValue(Personnage $personnage, Attribut $attribut): float
    {
        // Exemple pour PV max = Force * 2 + Vigueur * 3
        if ($attribut->slug === 'pv-max') {
            $force = $this->getCachedAttributeValue($personnage->id, 'force') ?? 0;
            $vigueur = $this->getCachedAttributeValue($personnage->id, 'vigueur') ?? 0;
            $derived = ($force * 2) + ($vigueur * 3);
            
            // Les attributs dérivés peuvent aussi avoir des modificateurs d'équipement/buffs
            $equipmentFlat = $this->getEquipmentFlatBonus($personnage, $attribut);
            $equipmentPercent = $this->getEquipmentPercentBonus($personnage, $attribut);
            $buffsFlat = $this->getTemporaryEffectsFlat($personnage, $attribut);
            $buffsPercent = $this->getTemporaryEffectsPercent($personnage, $attribut);
            
            $withEquipment = ($derived + $equipmentFlat) * (1 + $equipmentPercent / 100);
            $finalValue = ($withEquipment + $buffsFlat) * (1 + $buffsPercent / 100);
            
            return $this->applyBounds(floor($finalValue), $attribut);
        }
        
        // Ajouter d'autres formules dérivées ici
        return 0;
    }

    /**
     * Calcule la valeur d'un attribut calculé complexe
     */
    private function calculateComputedValue(Personnage $personnage, Attribut $attribut): float
    {
        // Logique pour les attributs calculés complexes
        // À implémenter selon les besoins spécifiques
        return 0;
    }

    /**
     * Récupère la valeur de base de la classe pour un attribut
     */
    private function getClassBaseValue(Personnage $personnage, Attribut $attribut): float
    {
        $baseValue = DB::table('classe_attributs')
            ->where('classe_id', $personnage->classe_id)
            ->where('attribut_id', $attribut->id)
            ->value('base_value');
            
        return $baseValue ?? $attribut->default_value ?? 0;
    }

    /**
     * Récupère l'override personnel du personnage pour un attribut
     */
    private function getPersonalOverride(Personnage $personnage, Attribut $attribut): float
    {
        $personalValue = DB::table('personnage_attributs')
            ->where('personnage_id', $personnage->id)
            ->where('attribut_id', $attribut->id)
            ->value('value');
            
        return $personalValue ?? 0;
    }

    /**
     * Récupère les bonus flat des équipements
     */
    private function getEquipmentFlatBonus(Personnage $personnage, Attribut $attribut): float
    {
        $inventaire = $personnage->inventaire;
        if (!$inventaire) {
            return 0;
        }
        
        return DB::table('inventaire_items')
            ->join('objets', 'inventaire_items.objet_id', '=', 'objets.id')
            ->join('objet_attributs', 'objets.id', '=', 'objet_attributs.objet_id')
            ->where('inventaire_items.inventaire_id', $inventaire->id)
            ->where('inventaire_items.is_equipped', true)
            ->where('objet_attributs.attribut_id', $attribut->id)
            ->where('objet_attributs.modifier_type', 'flat')
            ->sum('objet_attributs.modifier_value') ?? 0;
    }

    /**
     * Récupère les bonus en pourcentage des équipements
     */
    private function getEquipmentPercentBonus(Personnage $personnage, Attribut $attribut): float
    {
        $inventaire = $personnage->inventaire;
        if (!$inventaire) {
            return 0;
        }
        
        return DB::table('inventaire_items')
            ->join('objets', 'inventaire_items.objet_id', '=', 'objets.id')
            ->join('objet_attributs', 'objets.id', '=', 'objet_attributs.objet_id')
            ->where('inventaire_items.inventaire_id', $inventaire->id)
            ->where('inventaire_items.is_equipped', true)
            ->where('objet_attributs.attribut_id', $attribut->id)
            ->where('objet_attributs.modifier_type', 'percent')
            ->sum('objet_attributs.modifier_value') ?? 0;
    }

    /**
     * Récupère les bonus flat des effets temporaires
     * TODO: Implémenter quand la table character_effects sera créée
     */
    private function getTemporaryEffectsFlat(Personnage $personnage, Attribut $attribut): float
    {
        // Placeholder - à implémenter avec la table character_effects
        return 0;
    }

    /**
     * Récupère les bonus en pourcentage des effets temporaires
     * TODO: Implémenter quand la table character_effects sera créée
     */
    private function getTemporaryEffectsPercent(Personnage $personnage, Attribut $attribut): float
    {
        // Placeholder - à implémenter avec la table character_effects
        return 0;
    }

    /**
     * Applique les bornes min/max à une valeur
     */
    private function applyBounds(float $value, Attribut $attribut): float
    {
        if ($attribut->min_value !== null) {
            $value = max($value, $attribut->min_value);
        }
        if ($attribut->max_value !== null) {
            $value = min($value, $attribut->max_value);
        }
        
        return $value;
    }

    /**
     * Récupère la valeur mise en cache d'un attribut pour un personnage
     */
    private function getCachedAttributeValue(int $personnageId, string $attributSlug): ?float
    {
        return DB::table('personnage_attributs_cache')
            ->join('attributs', 'attributs.id', '=', 'personnage_attributs_cache.attribut_id')
            ->where('personnage_attributs_cache.personnage_id', $personnageId)
            ->where('attributs.slug', $attributSlug)
            ->value('personnage_attributs_cache.final_value');
    }

    /**
     * Invalide le cache pour un personnage et un attribut spécifique
     */
    public function invalidateCache(int $personnageId, int $attributId): void
    {
        DB::table('personnage_attributs_cache')
            ->where('personnage_id', $personnageId)
            ->where('attribut_id', $attributId)
            ->update([
                'needs_recalculation' => true,
                'updated_at' => now()
            ]);
    }

    /**
     * Invalide le cache pour tous les attributs d'un personnage
     */
    public function invalidateAllCache(int $personnageId): void
    {
        DB::table('personnage_attributs_cache')
            ->where('personnage_id', $personnageId)
            ->update([
                'needs_recalculation' => true,
                'updated_at' => now()
            ]);
    }

    /**
     * Invalide le cache pour un attribut sur tous les personnages
     */
    public function invalidateAttributeCache(int $attributId): void
    {
        DB::table('personnage_attributs_cache')
            ->where('attribut_id', $attributId)
            ->update([
                'needs_recalculation' => true,
                'updated_at' => now()
            ]);
    }
}