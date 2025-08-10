<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personnage;
use App\Services\CharacterStatsCacheManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CharacterStatsController extends Controller
{
    private CharacterStatsCacheManager $cacheManager;

    public function __construct(CharacterStatsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Récupère toutes les statistiques d'un personnage
     */
    public function getCharacterStats(Request $request, int $personnageId): JsonResponse
    {
        try {
            $personnage = Personnage::findOrFail($personnageId);
            
            // Vérifier que l'utilisateur a accès à ce personnage
            if ($personnage->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to character'
                ], 403);
            }
            
            $stats = $this->cacheManager->getAllFinalValues($personnageId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'personnage_id' => $personnageId,
                    'personnage_name' => $personnage->name,
                    'classe_name' => $personnage->classe->name,
                    'level' => $personnage->level,
                    'stats' => $stats->map(function ($stat) {
                        return [
                            'id' => $stat->id,
                            'name' => $stat->name,
                            'slug' => $stat->slug,
                            'type' => $stat->type,
                            'final_value' => $stat->final_value,
                            'calculated_at' => $stat->calculated_at,
                            'needs_recalculation' => $stat->needs_recalculation
                        ];
                    })
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving character stats', [
                'personnage_id' => $personnageId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to retrieve character stats'
            ], 500);
        }
    }

    /**
     * Récupère une statistique spécifique d'un personnage
     */
    public function getSpecificStat(Request $request, int $personnageId, string $attributSlug): JsonResponse
    {
        try {
            $personnage = Personnage::findOrFail($personnageId);
            
            // Vérifier que l'utilisateur a accès à ce personnage
            if ($personnage->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to character'
                ], 403);
            }
            
            $finalValue = $this->cacheManager->getFinalValueBySlug($personnageId, $attributSlug);
            
            if ($finalValue === null) {
                return response()->json([
                    'error' => 'Attribute not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'personnage_id' => $personnageId,
                    'attribute_slug' => $attributSlug,
                    'final_value' => $finalValue
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving specific character stat', [
                'personnage_id' => $personnageId,
                'attribute_slug' => $attributSlug,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to retrieve character stat'
            ], 500);
        }
    }

    /**
     * Force le recalcul des statistiques d'un personnage
     */
    public function recalculateStats(Request $request, int $personnageId): JsonResponse
    {
        try {
            $personnage = Personnage::findOrFail($personnageId);
            
            // Vérifier que l'utilisateur a accès à ce personnage
            if ($personnage->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to character'
                ], 403);
            }
            
            // Invalider tout le cache du personnage
            $this->cacheManager->invalidateAllCache($personnageId, 'manual_recalculation');
            
            // Déclencher un recalcul immédiat
            $this->cacheManager->triggerImmediateRecalculation($personnageId);
            
            return response()->json([
                'success' => true,
                'message' => 'Character stats recalculated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error recalculating character stats', [
                'personnage_id' => $personnageId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to recalculate character stats'
            ], 500);
        }
    }

    /**
     * Récupère les statistiques du cache (pour les administrateurs)
     */
    public function getCacheStats(Request $request): JsonResponse
    {
        try {
            // Vérifier les permissions d'administrateur
            if (!Auth::user()->isAdmin()) {
                return response()->json([
                    'error' => 'Admin access required'
                ], 403);
            }
            
            $stats = $this->cacheManager->getCacheStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving cache stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to retrieve cache stats'
            ], 500);
        }
    }

    /**
     * Compare les statistiques de plusieurs personnages
     */
    public function compareCharacters(Request $request): JsonResponse
    {
        $request->validate([
            'personnage_ids' => 'required|array|min:2|max:5',
            'personnage_ids.*' => 'integer|exists:personnages,id',
            'attribute_slugs' => 'array',
            'attribute_slugs.*' => 'string'
        ]);
        
        try {
            $personnageIds = $request->input('personnage_ids');
            $attributeSlugs = $request->input('attribute_slugs', []);
            
            $comparisons = [];
            
            foreach ($personnageIds as $personnageId) {
                $personnage = Personnage::findOrFail($personnageId);
                
                // Vérifier que l'utilisateur a accès à ce personnage
                if ($personnage->user_id !== Auth::id()) {
                    return response()->json([
                        'error' => "Unauthorized access to character {$personnageId}"
                    ], 403);
                }
                
                $stats = $this->cacheManager->getAllFinalValues($personnageId);
                
                // Filtrer par attributs spécifiques si demandé
                if (!empty($attributeSlugs)) {
                    $stats = $stats->whereIn('slug', $attributeSlugs);
                }
                
                $comparisons[] = [
                    'personnage_id' => $personnageId,
                    'personnage_name' => $personnage->name,
                    'classe_name' => $personnage->classe->name,
                    'level' => $personnage->level,
                    'stats' => $stats->keyBy('slug')->map(function ($stat) {
                        return [
                            'name' => $stat->name,
                            'final_value' => $stat->final_value
                        ];
                    })
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'comparisons' => $comparisons,
                    'compared_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error comparing characters', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to compare characters'
            ], 500);
        }
    }

    /**
     * Récupère l'historique des modifications de statistiques
     * TODO: À implémenter quand un système d'audit sera en place
     */
    public function getStatsHistory(Request $request, int $personnageId): JsonResponse
    {
        return response()->json([
            'message' => 'Stats history feature not yet implemented'
        ], 501);
    }
}