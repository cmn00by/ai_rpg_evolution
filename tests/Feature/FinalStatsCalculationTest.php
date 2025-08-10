<?php

namespace Tests\Feature;

use App\Models\Attribut;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\PersonnageAttributsCache;
use App\Models\User;
use App\Services\CharacterStatsCacheManager;
use App\Services\CharacterStatsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalStatsCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Classe $classe;
    private Personnage $personnage;
    private Attribut $forceAttribut;
    private Attribut $vigueurAttribut;
    private Attribut $pvMaxAttribut;
    private CharacterStatsCalculator $calculator;
    private CharacterStatsCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calculator = app(CharacterStatsCalculator::class);
        $this->cacheManager = app(CharacterStatsCacheManager::class);
        
        // Créer les données de test
        $this->user = User::factory()->create();
        $this->classe = Classe::factory()->create(['name' => 'Guerrier']);
        $this->personnage = Personnage::factory()->create([
            'user_id' => $this->user->id,
            'classe_id' => $this->classe->id,
            'name' => 'Test Character'
        ]);
        
        // Créer les attributs de test avec des slugs uniques
        $this->forceAttribut = Attribut::create([
            'name' => 'Force',
            'slug' => 'force',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true,
            'order' => 1
        ]);
        
        $this->vigueurAttribut = Attribut::create([
            'name' => 'Vigueur',
            'slug' => 'vigueur',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true,
            'order' => 2
        ]);
        
        $this->pvMaxAttribut = Attribut::create([
            'name' => 'PV Maximum',
            'slug' => 'pv-max',
            'type' => 'derived',
            'default_value' => 0,
            'min_value' => 1,
            'max_value' => null,
            'is_visible' => true,
            'order' => 3
        ]);
    }

    public function test_basic_attribute_calculation_with_class_base_only()
    {
        // Configurer la valeur de base pour la classe
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 15]
        );
        
        $finalValue = $this->calculator->calculateFinalValue($this->personnage, $this->forceAttribut);
        
        $this->assertEquals(15, $finalValue);
    }

    public function test_basic_attribute_calculation_with_personal_override()
    {
        // Valeur de base de la classe
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 15]
        );
        
        // Override personnel
        DB::table('personnage_attributs')->updateOrInsert(
            [
                'personnage_id' => $this->personnage->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['value' => 5] // +5 par rapport à la base
        );
        
        $finalValue = $this->calculator->calculateFinalValue($this->personnage, $this->forceAttribut);
        
        $this->assertEquals(20, $finalValue); // 15 + 5
    }

    public function test_attribute_bounds_are_applied()
    {
        // Valeur qui dépasse le maximum
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 95]
        );
        
        DB::table('personnage_attributs')->updateOrInsert(
            [
                'personnage_id' => $this->personnage->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['value' => 10] // Devrait donner 105, mais max est 100
        );
        
        $finalValue = $this->calculator->calculateFinalValue($this->personnage, $this->forceAttribut);
        
        $this->assertEquals(100, $finalValue); // Plafonné au maximum
    }

    public function test_derived_attribute_calculation()
    {
        // Configurer Force = 20
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 20]
        );
        
        // Configurer Vigueur = 15
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->vigueurAttribut->id
            ],
            ['base_value' => 15]
        );
        
        // Mettre en cache les valeurs de base
        $this->cacheManager->recalculateAndCache($this->personnage->id, $this->forceAttribut->id);
        $this->cacheManager->recalculateAndCache($this->personnage->id, $this->vigueurAttribut->id);
        
        // Calculer PV Max = Force * 2 + Vigueur * 3
        $finalValue = $this->calculator->calculateFinalValue($this->personnage, $this->pvMaxAttribut);
        
        $this->assertEquals(85, $finalValue); // (20 * 2) + (15 * 3) = 40 + 45 = 85
    }

    public function test_cache_manager_stores_and_retrieves_values()
    {
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 18]
        );
        
        // Premier calcul - devrait calculer et mettre en cache
        $firstValue = $this->cacheManager->getFinalValue($this->personnage->id, $this->forceAttribut->id);
        $this->assertEquals(18, $firstValue);
        
        // Vérifier que l'entrée est en cache
        $cached = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribut->id)
            ->first();
        
        $this->assertNotNull($cached);
        $this->assertEquals(18, $cached->final_value);
        $this->assertFalse($cached->needs_recalculation);
        
        // Deuxième appel - devrait utiliser le cache
        $secondValue = $this->cacheManager->getFinalValue($this->personnage->id, $this->forceAttribut->id);
        $this->assertEquals(18, $secondValue);
    }

    public function test_cache_invalidation_marks_for_recalculation()
    {
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 12]
        );
        
        // Calculer et mettre en cache
        $this->cacheManager->getFinalValue($this->personnage->id, $this->forceAttribut->id);
        
        // Invalider le cache
        $this->cacheManager->invalidateCache($this->personnage->id, $this->forceAttribut->id);
        
        // Vérifier que le cache est marqué pour recalcul
        $cached = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribut->id)
            ->first();
        
        $this->assertTrue($cached->needs_recalculation);
    }

    public function test_get_all_final_values_returns_visible_attributes()
    {
        // Configurer les valeurs de base
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 15]
        );
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->vigueurAttribut->id
            ],
            ['base_value' => 12]
        );
        
        // Calculer et mettre en cache
        $this->cacheManager->recalculateAndCache($this->personnage->id, $this->forceAttribut->id);
        $this->cacheManager->recalculateAndCache($this->personnage->id, $this->vigueurAttribut->id);
        
        $allValues = $this->cacheManager->getAllFinalValues($this->personnage->id);
        
        $this->assertCount(2, $allValues);
        
        $forceValue = $allValues->firstWhere('slug', 'force');
        $vigueurValue = $allValues->firstWhere('slug', 'vigueur');
        
        $this->assertEquals(15, $forceValue->final_value);
        $this->assertEquals(12, $vigueurValue->final_value);
    }

    public function test_cache_cleanup_for_deleted_attribute()
    {
        // Créer une entrée de cache
        DB::table('personnage_attributs_cache')->insert([
            'personnage_id' => $this->personnage->id,
            'attribut_id' => $this->forceAttribut->id,
            'final_value' => 15,
            'needs_recalculation' => false,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Vérifier que l'entrée existe
        $this->assertDatabaseHas('personnage_attributs_cache', [
            'personnage_id' => $this->personnage->id,
            'attribut_id' => $this->forceAttribut->id
        ]);
        
        // Nettoyer le cache pour cet attribut
        $this->cacheManager->cleanupDeletedAttributeCache($this->forceAttribut->id);
        
        // Vérifier que l'entrée a été supprimée
        $this->assertDatabaseMissing('personnage_attributs_cache', [
            'personnage_id' => $this->personnage->id,
            'attribut_id' => $this->forceAttribut->id
        ]);
    }

    public function test_immediate_recalculation_updates_multiple_attributes()
    {
        // Configurer les valeurs de base
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->forceAttribut->id
            ],
            ['base_value' => 20]
        );
        DB::table('classe_attributs')->updateOrInsert(
            [
                'classe_id' => $this->classe->id,
                'attribut_id' => $this->vigueurAttribut->id
            ],
            ['base_value' => 18]
        );
        
        // Déclencher un recalcul immédiat
        $this->cacheManager->triggerImmediateRecalculation(
            $this->personnage->id, 
            [$this->forceAttribut->id, $this->vigueurAttribut->id]
        );
        
        // Vérifier que les valeurs sont en cache
        $forceCache = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribut->id)
            ->first();
            
        $vigueurCache = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->vigueurAttribut->id)
            ->first();
        
        $this->assertEquals(20, $forceCache->final_value);
        $this->assertEquals(18, $vigueurCache->final_value);
        $this->assertFalse($forceCache->needs_recalculation);
        $this->assertFalse($vigueurCache->needs_recalculation);
    }

    public function test_cache_statistics_are_accurate()
    {
        // Créer quelques entrées de cache
        DB::table('personnage_attributs_cache')->insert([
            [
                'personnage_id' => $this->personnage->id,
                'attribut_id' => $this->forceAttribut->id,
                'final_value' => 15,
                'needs_recalculation' => false,
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'personnage_id' => $this->personnage->id,
                'attribut_id' => $this->vigueurAttribut->id,
                'final_value' => 12,
                'needs_recalculation' => true, // Marqué pour recalcul
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        
        $stats = $this->cacheManager->getCacheStats();
        
        $this->assertEquals(2, $stats['total_entries']);
        $this->assertEquals(1, $stats['needs_recalculation']);
        $this->assertEquals(50, $stats['cache_hit_rate']); // 1/2 * 100
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}