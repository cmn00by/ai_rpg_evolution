<?php

namespace Tests\Feature;

use App\Models\Attribut;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\User;
use App\Services\CharacterStatsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentStatsCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Classe $classe;
    private Personnage $personnage;
    private Attribut $forceAttribute;
    private Attribut $vigueurAttribute;
    private Attribut $pvMaxAttribute;
    private CharacterStatsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // Créer une classe de test
        $this->classe = Classe::create([
            'name' => 'Guerrier',
            'slug' => 'guerrier',
            'description' => 'Classe de guerrier pour les tests'
        ]);

        // Créer des attributs de test
        $this->forceAttribute = Attribut::create([
            'name' => 'Force',
            'slug' => 'force',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true
        ]);

        $this->vigueurAttribute = Attribut::create([
            'name' => 'Vigueur',
            'slug' => 'vigueur',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true
        ]);

        $this->pvMaxAttribute = Attribut::create([
            'name' => 'PV Maximum',
            'slug' => 'pv-max',
            'type' => 'derived',
            'default_value' => 0,
            'min_value' => 1,
            'max_value' => 1000,
            'is_visible' => true
        ]);

        // Associer les attributs à la classe avec des valeurs de base
        $this->classe->attributs()->attach($this->forceAttribute->id, ['base_value' => 15]);
        $this->classe->attributs()->attach($this->vigueurAttribute->id, ['base_value' => 12]);
        $this->classe->attributs()->attach($this->pvMaxAttribute->id, ['base_value' => 0]);

        // Créer un personnage de test
        $this->personnage = Personnage::create([
            'user_id' => $this->user->id,
            'classe_id' => $this->classe->id,
            'name' => 'Test Character',
            'level' => 1
        ]);

        // Initialiser le calculateur
        $this->calculator = new CharacterStatsCalculator();
    }

    /** @test */
    public function it_calculates_stats_with_flat_equipment_bonus()
    {
        // Simuler un bonus d'équipement flat (+5 Force)
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 5, 'percent' => 0]
        ];

        // Mock de la méthode getEquipmentBonuses
        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // Base classe (15) + bonus équipement flat (+5) = 20
        $this->assertEquals(20, $finalValue);
    }

    /** @test */
    public function it_calculates_stats_with_percent_equipment_bonus()
    {
        // Simuler un bonus d'équipement en pourcentage (+20% Force)
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 0, 'percent' => 20]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // Base classe (15) × (1 + 20/100) = 15 × 1.2 = 18
        $this->assertEquals(18, $finalValue);
    }

    /** @test */
    public function it_calculates_stats_with_mixed_equipment_bonuses()
    {
        // Simuler des bonus d'équipement mixtes (+3 flat + 10% Force)
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 3, 'percent' => 10]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // (Base classe (15) + flat (+3)) × (1 + 10/100) = 18 × 1.1 = 19.8 → 19 (floor)
        $this->assertEquals(19, $finalValue);
    }

    /** @test */
    public function it_calculates_stats_with_temporary_effects()
    {
        // Simuler des effets temporaires (+2 flat + 15% Force)
        $temporaryEffects = [
            $this->forceAttribute->id => ['flat' => 2, 'percent' => 15]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getTemporaryEffectBonuses'])
            ->getMock();
        
        $calculator->method('getTemporaryEffectBonuses')
            ->willReturn($temporaryEffects);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // (Base classe (15) + temp flat (+2)) × (1 + 15/100) = 17 × 1.15 = 19.55 → 19 (floor)
        $this->assertEquals(19, $finalValue);
    }

    /** @test */
    public function it_applies_correct_order_of_operations()
    {
        // Tester l'ordre complet : classe + perso + équipement + effets temporaires
        
        // Override personnage
        $this->personnage->attributs()->attach($this->forceAttribute->id, ['override_value' => 2]);
        
        // Bonus d'équipement
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 3, 'percent' => 10]
        ];
        
        // Effets temporaires
        $temporaryEffects = [
            $this->forceAttribute->id => ['flat' => 1, 'percent' => 5]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses', 'getTemporaryEffectBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);
        
        $calculator->method('getTemporaryEffectBonuses')
            ->willReturn($temporaryEffects);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // Ordre d'application :
        // 1. Base classe (15) + override perso (+2) = 17
        // 2. + flat équipement (+3) = 20
        // 3. × (1 + % équipement (10)/100) = 20 × 1.1 = 22
        // 4. + flat temporaire (+1) = 23
        // 5. × (1 + % temporaire (5)/100) = 23 × 1.05 = 24.15 → 24 (floor)
        $this->assertEquals(24, $finalValue);
    }

    /** @test */
    public function it_respects_min_max_bounds_with_equipment()
    {
        // Tester que les bornes min/max sont respectées même avec équipement
        
        // Bonus d'équipement très élevé
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 200, 'percent' => 0]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // Base (15) + équipement (200) = 215, mais max_value = 100
        $this->assertEquals(100, $finalValue);
        
        // Test avec malus pour atteindre le minimum
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => -50, 'percent' => 0]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // Base (15) + équipement (-50) = -35, mais min_value = 1
        $this->assertEquals(1, $finalValue);
    }

    /** @test */
    public function it_calculates_derived_stats_with_equipment_bonuses()
    {
        // Tester que les stats dérivées prennent en compte les bonus d'équipement
        
        // Bonus d'équipement sur Force et Vigueur
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => 5, 'percent' => 0],
            $this->vigueurAttribute->id => ['flat' => 3, 'percent' => 0]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->pvMaxAttribute->id
        );

        // Force finale : 15 + 5 = 20
        // Vigueur finale : 12 + 3 = 15
        // PV Max = (Force × 2) + Vigueur = (20 × 2) + 15 = 55
        $this->assertEquals(55, $finalValue);
    }

    /** @test */
    public function it_handles_negative_equipment_bonuses()
    {
        // Tester les malus d'équipement (objets maudits, etc.)
        
        $equipmentBonuses = [
            $this->forceAttribute->id => ['flat' => -3, 'percent' => -10]
        ];

        $calculator = $this->getMockBuilder(CharacterStatsCalculator::class)
            ->onlyMethods(['getEquipmentBonuses'])
            ->getMock();
        
        $calculator->method('getEquipmentBonuses')
            ->willReturn($equipmentBonuses);

        $finalValue = $calculator->calculateFinalValue(
            $this->personnage->id,
            $this->forceAttribute->id
        );

        // (Base (15) + flat (-3)) × (1 + (-10)/100) = 12 × 0.9 = 10.8 → 10 (floor)
        $this->assertEquals(10, $finalValue);
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        $this->personnage->attributs()->detach();
        $this->classe->attributs()->detach();
        
        $this->personnage->delete();
        $this->classe->delete();
        $this->forceAttribute->delete();
        $this->vigueurAttribute->delete();
        $this->pvMaxAttribute->delete();
        $this->user->delete();

        parent::tearDown();
    }
}