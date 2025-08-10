<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Personnage;
use App\Models\Classe;
use App\Models\Attribut;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;
use App\Models\Objet;
use App\Models\ObjetAttribut;
use App\Models\Inventaire;
use App\Models\InventaireItem;
use App\Services\InventoryManager;
use App\Services\CharacterStatsCalculator;
use Illuminate\Support\Facades\DB;

class InventoryEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Personnage $personnage;
    protected Classe $classe;
    protected Attribut $force;
    protected Attribut $constitution;
    protected RareteObjet $rare;
    protected SlotEquipement $slotArme;
    protected SlotEquipement $slotAnneau;
    protected Objet $epee;
    protected Objet $anneau;
    protected InventoryManager $inventoryManager;
    protected CharacterStatsCalculator $statsCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test
        $this->setupTestData();
        
        $this->inventoryManager = app(InventoryManager::class);
        $this->statsCalculator = app(CharacterStatsCalculator::class);
    }

    private function setupTestData(): void
    {
        // Créer les attributs
        $this->force = Attribut::create([
            'name' => 'Force',
            'slug' => 'force',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
        ]);
        
        $this->constitution = Attribut::create([
            'name' => 'Constitution',
            'slug' => 'constitution',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
        ]);

        // Créer une classe
        $this->classe = Classe::create([
            'name' => 'Guerrier',
            'slug' => 'guerrier',
        ]);
        
        // Valeurs de base de classe
        DB::table('classe_attributs')->insert([
            ['classe_id' => $this->classe->id, 'attribut_id' => $this->force->id, 'base_value' => 15],
            ['classe_id' => $this->classe->id, 'attribut_id' => $this->constitution->id, 'base_value' => 12],
        ]);

        // Créer un utilisateur et un personnage
        $this->user = User::factory()->create();
        $this->personnage = Personnage::create([
            'user_id' => $this->user->id,
            'name' => 'Test Hero',
            'classe_id' => $this->classe->id,
        ]);

        // Créer la rareté
        $this->rare = RareteObjet::create([
            'name' => 'Rare',
            'slug' => 'rare',
            'order' => 2,
            'color_hex' => '#3B82F6',
            'multiplier' => 1.5,
        ]);

        // Créer les slots
        $this->slotArme = SlotEquipement::create([
            'name' => 'Arme',
            'slug' => 'arme',
            'max_per_slot' => 1,
        ]);
        
        $this->slotAnneau = SlotEquipement::create([
            'name' => 'Anneau',
            'slug' => 'anneau',
            'max_per_slot' => 2,
        ]);

        // Créer les objets
        $this->epee = Objet::create([
            'name' => 'Épée enchantée',
            'slug' => 'epee-enchantee',
            'rarete_id' => $this->rare->id,
            'slot_id' => $this->slotArme->id,
            'stackable' => false,
            'base_durability' => 100,
            'buy_price' => 200,
            'sell_price' => 100,
        ]);
        
        $this->anneau = Objet::create([
            'name' => 'Anneau de force',
            'slug' => 'anneau-force',
            'rarete_id' => $this->rare->id,
            'slot_id' => $this->slotAnneau->id,
            'stackable' => false,
            'base_durability' => null,
            'buy_price' => 150,
            'sell_price' => 75,
        ]);

        // Créer les modificateurs d'attributs
        ObjetAttribut::create([
            'objet_id' => $this->epee->id,
            'attribut_id' => $this->force->id,
            'modifier_type' => 'flat',
            'modifier_value' => 5,
        ]);
        
        ObjetAttribut::create([
            'objet_id' => $this->epee->id,
            'attribut_id' => $this->force->id,
            'modifier_type' => 'percent',
            'modifier_value' => 10,
        ]);
        
        ObjetAttribut::create([
            'objet_id' => $this->anneau->id,
            'attribut_id' => $this->force->id,
            'modifier_type' => 'flat',
            'modifier_value' => 3,
        ]);
        
        ObjetAttribut::create([
            'objet_id' => $this->anneau->id,
            'attribut_id' => $this->constitution->id,
            'modifier_type' => 'flat',
            'modifier_value' => 2,
        ]);
    }

    public function test_inventory_creation(): void
    {
        $inventaire = $this->inventoryManager->createInventory($this->personnage);
        
        $this->assertInstanceOf(Inventaire::class, $inventaire);
        $this->assertEquals($this->personnage->id, $inventaire->personnage_id);
        
        // Vérifier que l'inventaire est lié au personnage
        $this->personnage->refresh();
        $this->assertNotNull($this->personnage->inventaire);
    }

    public function test_add_item_to_inventory(): void
    {
        $item = $this->inventoryManager->addItem($this->personnage, $this->epee, 1);
        
        $this->assertInstanceOf(InventaireItem::class, $item);
        $this->assertEquals($this->epee->id, $item->objet_id);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals(100, $item->durability); // base_durability de l'épée
        $this->assertFalse($item->is_equipped);
    }

    public function test_equip_item(): void
    {
        $item = $this->inventoryManager->addItem($this->personnage, $this->epee, 1);
        
        $result = $this->inventoryManager->equipItem($this->personnage, $item);
        
        $this->assertTrue($result);
        $item->refresh();
        $this->assertTrue($item->is_equipped);
    }

    public function test_unequip_item(): void
    {
        $item = $this->inventoryManager->addItem($this->personnage, $this->epee, 1);
        $this->inventoryManager->equipItem($this->personnage, $item);
        
        $result = $this->inventoryManager->unequipItem($this->personnage, $item);
        
        $this->assertTrue($result);
        $item->refresh();
        $this->assertFalse($item->is_equipped);
    }

    public function test_slot_limit(): void
    {
        // Ajouter deux anneaux
        $item1 = $this->inventoryManager->addItem($this->personnage, $this->anneau, 1);
        $item2 = $this->inventoryManager->addItem($this->personnage, $this->anneau, 1);
        
        // Équiper le premier anneau
        $result1 = $this->inventoryManager->equipItem($this->personnage, $item1);
        $this->assertTrue($result1);
        
        // Équiper le deuxième anneau (devrait fonctionner car max_per_slot = 2)
        $result2 = $this->inventoryManager->equipItem($this->personnage, $item2);
        $this->assertTrue($result2);
        
        // Essayer d'équiper un troisième anneau
        $item3 = $this->inventoryManager->addItem($this->personnage, $this->anneau, 1);
        $result3 = $this->inventoryManager->equipItem($this->personnage, $item3);
        $this->assertFalse($result3); // Devrait échouer
    }

    public function test_equipment_modifiers_affect_stats(): void
    {
        $inventaire = $this->inventoryManager->createInventory($this->personnage);
        
        // Stats de base (classe) : Force = 15, Constitution = 12
        $baseForce = $this->statsCalculator->calculateFinalValue($this->personnage, $this->force);
        $baseConstitution = $this->statsCalculator->calculateFinalValue($this->personnage, $this->constitution);
        
        $this->assertEquals(15, $baseForce);
        $this->assertEquals(12, $baseConstitution);
        
        // Équiper l'épée (+5 Force flat, +10% Force)
        $epeeItem = $this->inventoryManager->addItem($this->personnage, $this->epee, 1);
        $this->inventoryManager->equipItem($this->personnage, $epeeItem);
        
        // Recalculer les stats
        $newForce = $this->statsCalculator->calculateFinalValue($this->personnage, $this->force);
        $newConstitution = $this->statsCalculator->calculateFinalValue($this->personnage, $this->constitution);
        
        // Force = (15 + 5) * (1 + 10/100) = 20 * 1.1 = 22
        $this->assertEquals(22, $newForce);
        $this->assertEquals(12, $newConstitution); // Inchangée
        
        // Équiper l'anneau (+3 Force flat, +2 Constitution flat)
        $anneauItem = $this->inventoryManager->addItem($this->personnage, $this->anneau, 1);
        $this->inventoryManager->equipItem($this->personnage, $anneauItem);
        
        // Recalculer les stats
        $finalForce = $this->statsCalculator->calculateFinalValue($this->personnage, $this->force);
        $finalConstitution = $this->statsCalculator->calculateFinalValue($this->personnage, $this->constitution);
        
        // Force = (15 + 5 + 3) * (1 + 10/100) = 23 * 1.1 = 25.3 -> 25 (floor)
        $this->assertEquals(25, $finalForce);
        // Constitution = 12 + 2 = 14
        $this->assertEquals(14, $finalConstitution);
    }

    public function test_durability_system(): void
    {
        $item = $this->inventoryManager->addItem($this->personnage, $this->epee, 1);
        
        // Vérifier la durabilité initiale
        $this->assertEquals(100, $item->durability);
        
        // Équiper l'item d'abord
        $this->inventoryManager->equipItem($this->personnage, $item);
        
        // Réduire la durabilité
        $this->inventoryManager->reduceDurability($item, 30);
        $item->refresh();
        $this->assertEquals(70, $item->durability);
        
        // Vérifier que l'item est toujours équipé
        $this->assertTrue($item->is_equipped);
        
        // Réduire la durabilité à 0
        $this->inventoryManager->reduceDurability($item, 70);
        $item->refresh();
        
        $this->assertEquals(0, $item->durability);
        $this->assertFalse($item->is_equipped); // Devrait être automatiquement déséquipé
    }

    public function test_stackable_items(): void
    {
        // Créer un objet stackable
        $potion = Objet::create([
            'name' => 'Potion de soin',
            'slug' => 'potion-soin',
            'rarete_id' => $this->rare->id,
            'slot_id' => null,
            'stackable' => true,
            'base_durability' => null,
            'buy_price' => 10,
            'sell_price' => 5,
        ]);
        
        // Ajouter 5 potions
        $item1 = $this->inventoryManager->addItem($this->personnage, $potion, 5);
        $this->assertEquals(5, $item1->quantity);
        
        // Ajouter 3 potions supplémentaires (devrait fusionner)
        $item2 = $this->inventoryManager->addItem($this->personnage, $potion, 3);
        
        // Vérifier qu'il n'y a qu'un seul item avec la quantité totale
        $inventaire = $this->personnage->inventaire;
        $items = $inventaire->items()->where('objet_id', $potion->id)->get();
        $this->assertEquals(1, $items->count());
        $this->assertEquals(8, $items->first()->quantity);
    }
}