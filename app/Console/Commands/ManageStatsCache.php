<?php

namespace App\Console\Commands;

use App\Models\Attribut;
use App\Models\Personnage;
use App\Models\PersonnageAttributsCache;
use App\Services\CharacterStatsCacheManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageStatsCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stats:cache 
                            {action : Action to perform (rebuild, cleanup, stats, invalidate)}
                            {--personnage= : Specific character ID for targeted actions}
                            {--attribute= : Specific attribute ID for targeted actions}
                            {--force : Force the action without confirmation}
                            {--batch-size=1000 : Batch size for bulk operations}';

    /**
     * The console command description.
     */
    protected $description = 'Manage character stats cache (rebuild, cleanup, stats, invalidate)';

    private CharacterStatsCacheManager $cacheManager;

    public function __construct(CharacterStatsCacheManager $cacheManager)
    {
        parent::__construct();
        $this->cacheManager = $cacheManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'rebuild':
                return $this->rebuildCache();
            case 'cleanup':
                return $this->cleanupCache();
            case 'stats':
                return $this->showStats();
            case 'invalidate':
                return $this->invalidateCache();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: rebuild, cleanup, stats, invalidate');
                return 1;
        }
    }

    /**
     * Reconstruit complètement le cache
     */
    private function rebuildCache(): int
    {
        $personnageId = $this->option('personnage');
        $attributeId = $this->option('attribute');
        $batchSize = (int) $this->option('batch-size');
        
        if ($personnageId && $attributeId) {
            // Reconstruire pour un personnage et un attribut spécifique
            $this->info("Rebuilding cache for character {$personnageId}, attribute {$attributeId}...");
            $this->cacheManager->recalculateAndCache($personnageId, $attributeId);
            $this->info('Cache rebuilt successfully.');
            return 0;
        }
        
        if ($personnageId) {
            // Reconstruire pour un personnage spécifique
            $this->info("Rebuilding cache for character {$personnageId}...");
            $this->cacheManager->triggerImmediateRecalculation($personnageId);
            $this->info('Cache rebuilt successfully.');
            return 0;
        }
        
        if ($attributeId) {
            // Reconstruire pour un attribut spécifique
            if (!$this->option('force') && !$this->confirm("This will rebuild cache for attribute {$attributeId} on ALL characters. Continue?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            $this->info("Rebuilding cache for attribute {$attributeId} on all characters...");
            $this->cacheManager->triggerBatchRecalculation($attributeId, 'manual_rebuild', $batchSize);
            $this->info('Batch recalculation job dispatched.');
            return 0;
        }
        
        // Reconstruire tout le cache
        if (!$this->option('force') && !$this->confirm('This will rebuild the ENTIRE stats cache. This may take a while. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info('Rebuilding entire stats cache...');
        
        $attributeCount = Attribut::where('is_visible', true)->count();
        $personnageCount = Personnage::count();
        $totalOperations = $attributeCount * $personnageCount;
        
        $this->info("Total operations: {$totalOperations} ({$attributeCount} attributes × {$personnageCount} characters)");
        
        $bar = $this->output->createProgressBar($attributeCount);
        $bar->start();
        
        Attribut::where('is_visible', true)->chunk(10, function ($attributs) use ($bar, $batchSize) {
            foreach ($attributs as $attribut) {
                $this->cacheManager->triggerBatchRecalculation($attribut->id, 'full_rebuild', $batchSize);
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info('All batch recalculation jobs dispatched. Check queue status for progress.');
        
        return 0;
    }

    /**
     * Nettoie le cache
     */
    private function cleanupCache(): int
    {
        $this->info('Cleaning up stats cache...');
        
        // Nettoyer les entrées pour les attributs supprimés
        $deletedAttributes = PersonnageAttributsCache::cleanupDeletedAttributes();
        $this->info("Cleaned up {$deletedAttributes} entries for deleted attributes.");
        
        // Nettoyer les entrées pour les personnages supprimés
        $deletedPersonnages = PersonnageAttributsCache::cleanupDeletedPersonnages();
        $this->info("Cleaned up {$deletedPersonnages} entries for deleted characters.");
        
        // Nettoyer les entrées obsolètes (plus de 7 jours)
        $staleMinutes = 7 * 24 * 60; // 7 jours
        $staleEntries = PersonnageAttributsCache::getStaleEntries($staleMinutes)->count();
        
        if ($staleEntries > 0) {
            if ($this->option('force') || $this->confirm("Found {$staleEntries} stale cache entries (older than 7 days). Mark them for recalculation?")) {
                PersonnageAttributsCache::getStaleEntries($staleMinutes)->update([
                    'needs_recalculation' => true,
                    'updated_at' => now()
                ]);
                $this->info("Marked {$staleEntries} stale entries for recalculation.");
            }
        } else {
            $this->info('No stale entries found.');
        }
        
        $this->info('Cache cleanup completed.');
        return 0;
    }

    /**
     * Affiche les statistiques du cache
     */
    private function showStats(): int
    {
        $stats = $this->cacheManager->getCacheStats();
        $modelStats = PersonnageAttributsCache::getCacheStatistics();
        
        $this->info('=== Character Stats Cache Statistics ===');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cache Entries', number_format($stats['total_entries'])],
                ['Needs Recalculation', number_format($stats['needs_recalculation'])],
                ['Cache Hit Rate', round($stats['cache_hit_rate'], 2) . '%'],
                ['Stale Entries', number_format($modelStats['stale_entries'])],
                ['Freshness Rate', round($modelStats['freshness_rate'], 2) . '%'],
                ['Oldest Entry', $stats['oldest_entry'] ?? 'N/A'],
                ['Newest Entry', $stats['newest_entry'] ?? 'N/A']
            ]
        );
        
        $this->newLine();
        
        // Statistiques par attribut
        $attributeStats = DB::table('personnage_attributs_cache')
            ->join('attributs', 'attributs.id', '=', 'personnage_attributs_cache.attribut_id')
            ->select(
                'attributs.name',
                'attributs.slug',
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(CASE WHEN needs_recalculation THEN 1 ELSE 0 END) as needs_recalc')
            )
            ->groupBy('attributs.id', 'attributs.name', 'attributs.slug')
            ->orderBy('total_entries', 'desc')
            ->get();
        
        if ($attributeStats->isNotEmpty()) {
            $this->info('=== Cache Entries by Attribute ===');
            $this->table(
                ['Attribute', 'Slug', 'Total Entries', 'Needs Recalc'],
                $attributeStats->map(function ($stat) {
                    return [
                        $stat->name,
                        $stat->slug,
                        number_format($stat->total_entries),
                        number_format($stat->needs_recalc)
                    ];
                })->toArray()
            );
        }
        
        return 0;
    }

    /**
     * Invalide le cache
     */
    private function invalidateCache(): int
    {
        $personnageId = $this->option('personnage');
        $attributeId = $this->option('attribute');
        
        if ($personnageId && $attributeId) {
            // Invalider pour un personnage et un attribut spécifique
            $this->info("Invalidating cache for character {$personnageId}, attribute {$attributeId}...");
            $this->cacheManager->invalidateCache($personnageId, $attributeId, 'manual_invalidation');
            $this->info('Cache invalidated successfully.');
            return 0;
        }
        
        if ($personnageId) {
            // Invalider pour un personnage spécifique
            $this->info("Invalidating all cache for character {$personnageId}...");
            $this->cacheManager->invalidateAllCache($personnageId, 'manual_invalidation');
            $this->info('Cache invalidated successfully.');
            return 0;
        }
        
        if ($attributeId) {
            // Invalider pour un attribut spécifique
            if (!$this->option('force') && !$this->confirm("This will invalidate cache for attribute {$attributeId} on ALL characters. Continue?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            $this->info("Invalidating cache for attribute {$attributeId} on all characters...");
            $this->cacheManager->invalidateAttributeCache($attributeId, 'manual_invalidation');
            $this->info('Cache invalidated successfully.');
            return 0;
        }
        
        // Invalider tout le cache
        if (!$this->option('force') && !$this->confirm('This will invalidate the ENTIRE stats cache. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info('Invalidating entire stats cache...');
        
        $updatedCount = DB::table('personnage_attributs_cache')->update([
            'needs_recalculation' => true,
            'updated_at' => now()
        ]);
        
        $this->info("Invalidated {$updatedCount} cache entries.");
        
        return 0;
    }
}