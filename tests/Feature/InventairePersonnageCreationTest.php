<?php

namespace Tests\Feature;

use App\Filament\Resources\InventairePersonnageResource\Pages\CreateInventairePersonnage;
use App\Models\User;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;
use App\Models\Objet;
use App\Models\InventairePersonnage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventairePersonnageCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_inventory_when_missing_for_personnage(): void
    {
        $user = User::factory()->create();

        $classe = Classe::create([
            'name' => 'Guerrier',
            'slug' => 'guerrier',
        ]);

        $personnage = Personnage::create([
            'user_id' => $user->id,
            'classe_id' => $classe->id,
            'name' => 'Test Hero',
        ]);

        $rarete = RareteObjet::create([
            'name' => 'Commun',
            'slug' => 'commun',
            'order' => 1,
            'color_hex' => '#000000',
            'multiplier' => 1,
        ]);

        $slot = SlotEquipement::create([
            'name' => 'Arme',
            'slug' => 'arme',
            'max_per_slot' => 1,
        ]);

        $objet = Objet::create([
            'name' => 'Épée simple',
            'slug' => 'epee-simple',
            'rarete_id' => $rarete->id,
            'slot_id' => $slot->id,
            'stackable' => false,
            'base_durability' => 100,
            'buy_price' => 10,
            'sell_price' => 5,
        ]);

        $page = new class extends CreateInventairePersonnage {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $data = [
            'personnage_id' => $personnage->id,
            'objet_id' => $objet->id,
            'quantite' => 1,
            'is_equipped' => false,
        ];

        $mutated = $page->mutate($data);

        $item = InventairePersonnage::create($mutated);

        $this->assertDatabaseHas('inventaires', [
            'id' => $item->inventaire_id,
            'personnage_id' => $personnage->id,
        ]);

        $this->assertDatabaseHas('inventaire_items', [
            'id' => $item->id,
            'inventaire_id' => $item->inventaire_id,
            'objet_id' => $objet->id,
            'quantity' => 1,
        ]);
    }
}

