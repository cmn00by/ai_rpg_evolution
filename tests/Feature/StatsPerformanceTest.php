<?php

namespace Tests\Feature;

use App\Jobs\RecalculateCharacterStatsBatch;
use App\Models\Attribut;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\PersonnageAttributsCache;
use App\Models\User;
use App\Services\CharacterStatsCacheManager;
use App\Services\CharacterStatsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StatsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Classe $classe;
    private array $attributs = [];
    private array $personnages = [];
    private CharacterStatsCacheManager $cacheManager;
    private CharacterStatsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer une classe de test
        $this->classe = Classe::create([
            'name' => 'Guerrier',
            'slug' => 'guerrier',
            'description' => 'Classe de guerrier pour les tests de performance'
        ]);

        // Créer plusieurs attributs
        $attributsData = [
            ['name' => 'Force', 'slug' => 'force', 'type' => 'int', 'base_value' => 15],
            ['name' => 'Vigueur', 'slug' => 'vigueur', 'type' => 'int', 'base_value' => 12],
            ['name' => 'Agilité', 'slug' => 'agilite', 'type' => 'int', 'base_value' => 10],
            ['name' => 'Intelligence', 'slug' => 'intelligence', 'type' => 'int', 'base_value' => 8],
            ['name' => 'PV Maximum', 'slug' => 'pv-max', 'type' => 'derived', 'base_value' => 0],
        ];

        foreach ($attributsData as $data) {
            $attribut = Attribut::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'type' => $data['type'],
                'default_value' => 10,
                'min_value' => 1,
                'max_value' => 100,
                'is_visible' => true
            ]);

            $this->classe->attributs()->attach($attribut->id, ['base_value' => $data['base_value']]);
            $this->attributs[] = $attribut;
        }

        // Initialiser les services
        $this->cacheManager = new CharacterStatsCacheManager();
        $this->calculator = new CharacterStatsCalculator();
    }

    /** @test */
    public function it_handles_batch_recalculation_efficiently()
    {
        Queue::fake();

        // Créer un nombre modéré de personnages pour le test
        $this->createTestCharacters(50);

        // Mesurer le temps de création du cache initial
        $startTime = microtime(true);
        
        foreach ($this->personnages as $personnage) {
            foreach ($this->attributs as $attribut) {
                $this->cacheManager->recalculateAndCache($personnage->id, $attribut->id);
            }
        }
        
        $initialCacheTime = microtime(true) - $startTime;
        
        // Vérifier que le cache a été créé
        $cacheCount = PersonnageAttributsCache::count();
        $expectedCount = count($this->personnages) * count($this->attributs);
        $this->assertEquals($expectedCount, $cacheCount);
        
        $this->info("Initial cache creation for {$expectedCount} entries took: {$initialCacheTime}s");
        
        // Invalider tout le cache
        $startTime = microtime(true);
        
        foreach ($this->attributs as $attribut) {
            $this->cacheManager->invalidateAttributeCache($attribut->id, 'performance_test');
        }
        
        $invalidationTime = microtime(true) - $startTime;
        $this->info("Cache invalidation took: {$invalidationTime}s");
        
        // Vérifier que tout le cache a été invalidé
        $invalidatedCount = PersonnageAttributsCache::where('needs_recalculation', true)->count();
        $this->assertEquals($expectedCount, $invalidatedCount);
        
        // Déclencher des recalculs en batch
        $startTime = microtime(true);
        
        foreach ($this->attributs as $attribut) {
            $this->cacheManager->triggerBatchRecalculation($attribut->id, 'performance_test', 25);
        }
        
        $batchDispatchTime = microtime(true) - $startTime;
        $this->info("Batch job dispatch took: {$batchDispatchTime}s");
        
        // Vérifier que les jobs ont été dispatchés
        Queue::assertPushed(RecalculateCharacterStatsBatch::class, count($this->attributs));
    }

    /** @test */
    public function it_calculates_stats_efficiently_for_single_character()
    {
        // Créer un personnage
        $this->createTestCharacters(1);
        $personnage = $this->personnages[0];
        
        // Ajouter quelques overrides
        $personnage->attributs()->attach($this->attributs[0]->id, ['override_value' => 5]);
        $personnage->attributs()->attach($this->attributs[1]->id, ['override_value' => 3]);
        
        // Mesurer le temps de calcul pour tous les attributs
        $startTime = microtime(true);
        
        $results = [];
        foreach ($this->attributs as $attribut) {
            $results[$attribut->slug] = $this->calculator->calculateFinalValue(
                $personnage->id,
                $attribut->id
            );
        }
        
        $calculationTime = microtime(true) - $startTime;
        
        $this->info("Single character stats calculation took: {$calculationTime}s");
        
        // Vérifier que les calculs sont corrects
        $this->assertEquals(20, $results['force']); // 15 + 5
        $this->assertEquals(15, $results['vigueur']); // 12 + 3
        $this->assertEquals(10, $results['agilite']); // 10 + 0
        $this->assertEquals(8, $results['intelligence']); // 8 + 0
        $this->assertEquals(55, $results['pv-max']); // (20 * 2) + 15
        
        // Le calcul devrait être très rapide (< 0.1s pour 5 attributs)
        $this->assertLessThan(0.1, $calculationTime);
    }

    /** @test */
    public function it_retrieves_cached_stats_efficiently()
    {
        // Créer des personnages et leur cache
        $this->createTestCharacters(20);
        
        // Créer le cache pour tous
        foreach ($this->personnages as $personnage) {
            foreach ($this->attributs as $attribut) {
                PersonnageAttributsCache::create([
                    'personnage_id' => $personnage->id,
                    'attribut_id' => $attribut->id,
                    'final_value' => rand(10, 50),
                    'needs_recalculation' => false,
                    'calculated_at' => now()
                ]);
            }
        }
        
        // Mesurer le temps de récupération du cache
        $startTime = microtime(true);
        
        foreach ($this->personnages as $personnage) {
            $stats = $this->cacheManager->getAllVisibleStats($personnage->id);
            $this->assertCount(count($this->attributs), $stats);
        }
        
        $retrievalTime = microtime(true) - $startTime;
        
        $this->info("Cache retrieval for " . count($this->personnages) . " characters took: {$retrievalTime}s");
        
        // La récupération devrait être très rapide
        $this->assertLessThan(0.5, $retrievalTime);
    }

    /** @test */
    public function it_handles_cache_statistics_efficiently()
    {
        // Créer des données de test
        $this->createTestCharacters(30);
        
        // Créer un mix de cache valide et invalide
        foreach ($this->personnages as $index => $personnage) {
            foreach ($this->attributs as $attribut) {
                PersonnageAttributsCache::create([
                    'personnage_id' => $personnage->id,
                    'attribut_id' => $attribut->id,
                    'final_value' => rand(10, 50),
                    'needs_recalculation' => $index % 3 === 0, // 1/3 invalide
                    'calculated_at' => $index % 5 === 0 ? now()->subDays(10) : now() // Quelques entrées anciennes
                ]);
            }
        }
        
        // Mesurer le temps de calcul des statistiques
        $startTime = microtime(true);
        
        $stats = $this->cacheManager->getCacheStats();
        $modelStats = PersonnageAttributsCache::getCacheStatistics();
        
        $statsTime = microtime(true) - $startTime;
        
        $this->info("Cache statistics calculation took: {$statsTime}s");
        
        // Vérifier que les statistiques sont cohérentes
        $expectedTotal = count($this->personnages) * count($this->attributs);
        $this->assertEquals($expectedTotal, $stats['total_entries']);
        
        $expectedInvalid = ceil(count($this->personnages) / 3) * count($this->attributs);
        $this->assertEquals($expectedInvalid, $stats['needs_recalculation']);
        
        // Le calcul des stats devrait être rapide
        $this->assertLessThan(0.2, $statsTime);
    }

    /** @test */
    public function it_processes_batch_job_efficiently()
    {
        // Créer des personnages
        $this->createTestCharacters(25);
        $attribut = $this->attributs[0];
        
        // Créer des entrées de cache qui ont besoin de recalcul
        foreach ($this->personnages as $personnage) {
            PersonnageAttributsCache::create([
                'personnage_id' => $personnage->id,
                'attribut_id' => $attribut->id,
                'final_value' => 0,
                'needs_recalculation' => true,
                'calculated_at' => null
            ]);
        }
        
        // Créer et exécuter le job
        $job = new RecalculateCharacterStatsBatch($attribut->id, 'performance_test', 10);
        
        $startTime = microtime(true);
        $job->handle();
        $jobTime = microtime(true) - $startTime;
        
        $this->info("Batch job execution for 25 characters took: {$jobTime}s");
        
        // Vérifier que toutes les entrées ont été recalculées
        $recalculatedCount = PersonnageAttributsCache::where('attribut_id', $attribut->id)
            ->where('needs_recalculation', false)
            ->whereNotNull('calculated_at')
            ->count();
        
        $this->assertEquals(count($this->personnages), $recalculatedCount);
        
        // Le job devrait être raisonnablement rapide
        $this->assertLessThan(2.0, $jobTime);
    }

    /** @test */
    public function it_handles_memory_usage_efficiently_for_large_datasets()
    {
        // Créer un dataset plus important
        $this->createTestCharacters(100);
        
        $memoryBefore = memory_get_usage(true);
        
        // Créer le cache pour tous
        foreach ($this->personnages as $personnage) {
            foreach ($this->attributs as $attribut) {
                $this->cacheManager->recalculateAndCache($personnage->id, $attribut->id);
            }
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->info("Memory used for 500 cache operations: " . round($memoryUsed / 1024 / 1024, 2) . " MB");
        
        // L'utilisation mémoire ne devrait pas être excessive (< 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed);
        
        // Vérifier que tout le cache a été créé
        $cacheCount = PersonnageAttributsCache::count();
        $expectedCount = count($this->personnages) * count($this->attributs);
        $this->assertEquals($expectedCount, $cacheCount);
    }

    /**
     * Créer des personnages de test
     */
    private function createTestCharacters(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com"
            ]);
            
            $personnage = Personnage::create([
                'user_id' => $user->id,
                'classe_id' => $this->classe->id,
                'name' => "Character {$i}",
                'level' => rand(1, 10)
            ]);
            
            $this->personnages[] = $personnage;
        }
    }

    /**
     * Afficher une information de performance
     */
    private function info(string $message): void
    {
        if (app()->environment('testing')) {
            echo "\n[PERF] {$message}";
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        PersonnageAttributsCache::truncate();
        
        foreach ($this->personnages as $personnage) {
            $personnage->attributs()->detach();
            $personnage->delete();
            $personnage->user->delete();
        }
        
        $this->classe->attributs()->detach();
        $this->classe->delete();
        
        foreach ($this->attributs as $attribut) {
            $attribut->delete();
        }

        parent::tearDown();
    }
}