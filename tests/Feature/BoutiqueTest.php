<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Personnage;
use App\Models\Boutique;
use App\Models\BoutiqueItem;
use App\Models\Objet;
use App\Models\RareteObjet;
use App\Models\Inventaire;
use App\Models\InventaireItem;
use App\Models\AchatHistorique;
use App\Models\SlotEquipement;
use App\Services\BoutiqueService;
use App\Exceptions\BoutiqueException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BoutiqueTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Personnage $personnage;
    private Boutique $boutique;
    private BoutiqueItem $boutiqueItem;
    private Objet $objet;
    private Inventaire $inventaire;
    private BoutiqueService $boutiqueService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->personnage = Personnage::factory()->create([
            'user_id' => $this->user->id,
            'gold' => 1000
        ]);
        
        // Créer un inventaire pour le personnage
        $this->inventaire = Inventaire::factory()->create([
            'personnage_id' => $this->personnage->id
        ]);
        
        $this->boutique = Boutique::factory()->create([
            'is_active' => true,
            'config_json' => [
                'tax_rate' => 10,
                'discount_rate' => 5,
                'limits' => [
                    'max_per_transaction' => 10,
                    'max_per_day' => 50
                ]
            ]
        ]);
        
        $this->objet = Objet::factory()->create([
            'buy_price' => 100,
            'sell_price' => 50,
            'stackable' => true
        ]);
        
        $this->boutiqueItem = BoutiqueItem::factory()->create([
            'boutique_id' => $this->boutique->id,
            'objet_id' => $this->objet->id,
            'stock' => 10,
            'allow_buy' => true,
            'allow_sell' => true
        ]);
        
        $this->boutiqueService = app(BoutiqueService::class);
    }

    public function test_can_purchase_item_successfully()
    {
        $quantity = 2;
        
        $achatHistorique = $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            $quantity
        );
        
        // Vérifier l'historique d'achat
        $this->assertInstanceOf(AchatHistorique::class, $achatHistorique);
        $this->assertEquals('buy', $achatHistorique->type);
        $this->assertEquals($quantity, $achatHistorique->qty);
        $this->assertEquals($this->personnage->id, $achatHistorique->personnage_id);
        
        // Vérifier la déduction de l'or
        $this->personnage->refresh();
        $expectedOr = 1000 - ($achatHistorique->total_price);
        $this->assertEquals($expectedOr, $this->personnage->gold);
        
        // Vérifier la réduction du stock
        $this->boutiqueItem->refresh();
        $this->assertEquals(8, $this->boutiqueItem->stock);
        
        // Vérifier l'ajout à l'inventaire
        $inventaire = $this->personnage->inventaire;
        $this->assertNotNull($inventaire);
        $inventaireItem = InventaireItem::where('inventaire_id', $inventaire->id)
            ->where('objet_id', $this->objet->id)
            ->first();
        $this->assertNotNull($inventaireItem);
        $this->assertEquals($quantity, $inventaireItem->quantity);
    }

    public function test_cannot_purchase_with_insufficient_gold()
    {
        $this->personnage->update(['gold' => 10]); // Pas assez d'or
        
        $this->expectException(BoutiqueException::class);
        $this->expectExceptionMessage('Solde insuffisant');
        
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            5
        );
    }

    public function test_cannot_purchase_with_insufficient_stock()
    {
        $this->expectException(BoutiqueException::class);
        $this->expectExceptionMessage('Stock insuffisant');
        
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            15 // Plus que le stock disponible (10)
        );
    }

    public function test_can_sell_item_successfully()
    {
        // Ajouter l'objet à l'inventaire existant
        InventaireItem::factory()->create([
            'inventaire_id' => $this->inventaire->id,
            'objet_id' => $this->objet->id,
            'quantity' => 5,
            'is_equipped' => false,
        ]);
        
        $quantity = 2;
        $initialOr = $this->personnage->gold;
        
        $achatHistorique = $this->boutiqueService->sellItem(
            $this->personnage,
            $this->boutique,
            $this->objet,
            $quantity
        );
        
        // Vérifier l'historique de vente
        $this->assertInstanceOf(AchatHistorique::class, $achatHistorique);
        $this->assertEquals('sell', $achatHistorique->type);
        $this->assertEquals($quantity, $achatHistorique->qty);
        
        // Vérifier l'ajout de l'or
        $this->personnage->refresh();
        $expectedOr = $initialOr + $achatHistorique->total_price;
        $this->assertEquals($expectedOr, $this->personnage->gold);
        
        // Vérifier la réduction de l'inventaire
        $inventaireItem = InventaireItem::where('inventaire_id', $this->inventaire->id)
            ->where('objet_id', $this->objet->id)
            ->first();
        $this->assertEquals(3, $inventaireItem->quantity);
    }

    public function test_cannot_sell_item_not_in_inventory()
    {
        $this->expectException(BoutiqueException::class);
        $this->expectExceptionMessage('Quantité insuffisante dans l\'inventaire');
        
        $this->boutiqueService->sellItem(
            $this->personnage,
            $this->boutique,
            $this->objet,
            1
        );
    }

    public function test_concurrent_purchases_handle_stock_correctly()
    {
        // Simuler deux achats simultanés sur le dernier stock
        $this->boutiqueItem->update(['stock' => 1]);
        
        $personnage2 = Personnage::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
            'gold' => 1000
        ]);
        
        $success1 = false;
        $success2 = false;
        $exception1 = null;
        $exception2 = null;
        
        // Premier achat
        try {
            DB::transaction(function () {
                $this->boutiqueService->purchaseItem(
                    $this->personnage,
                    $this->boutique,
                    $this->boutiqueItem,
                    1
                );
            });
            $success1 = true;
        } catch (BoutiqueException $e) {
            $exception1 = $e;
        }
        
        // Deuxième achat (devrait échouer)
        try {
            DB::transaction(function () use ($personnage2) {
                $this->boutiqueService->purchaseItem(
                    $personnage2,
                    $this->boutique,
                    $this->boutiqueItem->fresh(),
                    1
                );
            });
            $success2 = true;
        } catch (BoutiqueException $e) {
            $exception2 = $e;
        }
        
        // Un seul achat devrait réussir
        $this->assertTrue($success1 XOR $success2);
        
        // Vérifier que le stock est à 0
        $this->boutiqueItem->refresh();
        $this->assertEquals(0, $this->boutiqueItem->stock);
    }

    public function test_object_catalog_rules()
    {
        // Créer les slots nécessaires
        $weaponSlot = SlotEquipement::create([
            'name' => 'Weapon',
            'slug' => 'weapon',
            'max_per_slot' => 1
        ]);
        
        $armorSlot = SlotEquipement::create([
            'name' => 'Armor', 
            'slug' => 'armor',
            'max_per_slot' => 1
        ]);
        
        $accessorySlot = SlotEquipement::create([
            'name' => 'Accessory',
            'slug' => 'accessory', 
            'max_per_slot' => 1
        ]);
        
        // Tester les règles de slot
        $this->boutique->update([
            'config_json' => [
                'allowed_slots' => ['weapon', 'armor']
            ]
        ]);
        
        $this->objet->update(['slot_id' => $accessorySlot->id]);
        $this->objet->refresh();
        
        $this->assertFalse($this->boutique->isObjectAllowed($this->objet));
        
        $this->objet->update(['slot_id' => $weaponSlot->id]);
        $this->objet->refresh();
        $this->assertTrue($this->boutique->isObjectAllowed($this->objet));
        
        // Tester la blacklist
        $this->boutique->update([
            'config_json' => [
                'blacklist' => [$this->objet->id]
            ]
        ]);
        
        $this->assertFalse($this->boutique->isObjectAllowed($this->objet));
        
        // Tester la whitelist
        $this->boutique->update([
            'config_json' => [
                'whitelist' => [$this->objet->id]
            ]
        ]);
        
        $this->assertTrue($this->boutique->isObjectAllowed($this->objet));
    }

    public function test_transaction_limits()
    {
        // Tester la limite par transaction
        $this->expectException(BoutiqueException::class);
        $this->expectExceptionMessage('Quantité maximale par transaction');
        
        $this->boutiqueItem->update(['stock' => 20]);
        
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            15 // Dépasse la limite de 10
        );
    }

    public function test_daily_limits()
    {
        // Donner plus d'or au personnage pour ce test
        $this->personnage->update(['gold' => 20000]);
        $this->personnage->refresh();
        
        $this->boutiqueItem->update(['stock' => 100]);
        
        // Effectuer plusieurs achats pour atteindre la limite quotidienne
        // Acheter 10 items (limite par transaction)
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            10
        );
        $this->personnage->refresh();
        
        // Acheter encore 10 items (total: 20)
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            10
        );
        $this->personnage->refresh();
        
        // Acheter encore 10 items (total: 30)
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            10
        );
        $this->personnage->refresh();
        
        // Acheter encore 10 items (total: 40)
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            10
        );
        $this->personnage->refresh();
        
        // Acheter encore 10 items (total: 50, limite atteinte)
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            10
        );
        $this->personnage->refresh();
        
        // Tenter un achat supplémentaire qui dépasserait la limite quotidienne
        $this->expectException(BoutiqueException::class);
        $this->expectExceptionMessage('Limite quotidienne atteinte');
        
        $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            1 // 50 + 1 = 51, ce qui dépasse la limite de 50
        );
    }

    public function test_automatic_restock()
    {
        $this->boutiqueItem->update([
            'stock' => 5,
            'restock_rule' => [
                'freq' => 'daily',
                'at' => '03:00',
                'qty' => 10,
                'cap' => 50
            ],
            'last_restock' => now()->subDay() // Dernier restock hier
        ]);
        
        $restockedCount = $this->boutiqueService->performAutomaticRestock();
        
        $this->assertEquals(1, $restockedCount);
        
        $this->boutiqueItem->refresh();
        $this->assertEquals(15, $this->boutiqueItem->stock); // 5 + 10
        $this->assertNotNull($this->boutiqueItem->last_restock);
    }

    public function test_price_calculation_with_taxes_and_discounts()
    {
        $this->personnage->update(['reputation' => 50]);
        $this->personnage->refresh(); // Refresh to get updated values
        
        // Créer une rareté spécifique avec order 2 pour un multiplicateur de 1.5
        $rarete = RareteObjet::factory()->create([
            'name' => 'Test Rarity',
            'slug' => 'test-rarity-' . uniqid(),
            'order' => 2,
            'color_hex' => '#ff0000',
            'multiplier' => 1.5
        ]);
        $this->objet->update(['rarete_id' => $rarete->id]);
        
        $this->boutique->update([
            'config_json' => [
                'tax_rate' => 10, // 10% de taxe
                'discount_rate' => 5, // 5% de remise
                'reputation_discount' => [
                    'rate' => 0.1, // 0.1% par point de réputation
                    'max_reputation' => 100
                ],
                'dynamic_pricing' => [
                    'enabled' => false // Désactiver le pricing dynamique pour ce test
                ]
            ]
        ]);
        
        $finalPrice = $this->boutiqueItem->calculateFinalPrice('buy', $this->personnage);
        
        // Prix de base: 100 * 1.5 (rareté) = 150
        // Taxe: 150 * 0.10 = 15
        // Remise: 150 * 0.05 = 7.5
        // Remise réputation: 150 * (50 * 0.1 / 100) = 7.5
        // Prix final: 150 + 15 - 7.5 - 7.5 = 150
        
        $this->assertEquals(150, $finalPrice);
    }

    public function test_purchase_history_tracking()
    {
        // Donner plus d'or au personnage pour ce test
        $this->personnage->update(['gold' => 5000]);
        
        $quantity = 3;
        
        $achatHistorique = $this->boutiqueService->purchaseItem(
            $this->personnage,
            $this->boutique,
            $this->boutiqueItem,
            $quantity
        );
        
        // Vérifier les métadonnées
        $meta = $achatHistorique->meta_json;
        $this->assertArrayHasKey('solde_avant', $meta);
        $this->assertArrayHasKey('solde_apres', $meta);
        $this->assertArrayHasKey('taxes', $meta);
        $this->assertArrayHasKey('remises', $meta);
        $this->assertArrayHasKey('prix_base', $meta);
        
        $this->assertEquals(5000, $meta['solde_avant']);
        // Vérifier que le solde après correspond au calcul attendu
        $expectedSoldeApres = 5000 - $achatHistorique->total_price;
        $this->assertEquals($expectedSoldeApres, $meta['solde_apres']);
        $this->assertEquals($meta['solde_apres'], $this->personnage->fresh()->gold);
        
        // Vérifier que la somme est correcte
        $expectedTotal = $achatHistorique->unit_price * $quantity;
        $this->assertEquals($expectedTotal, $achatHistorique->total_price);
    }
}