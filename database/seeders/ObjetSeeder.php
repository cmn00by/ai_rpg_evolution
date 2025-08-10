<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Objet;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;

class ObjetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commun = RareteObjet::where('slug', 'commun')->first();
        $rare = RareteObjet::where('slug', 'rare')->first();
        $epique = RareteObjet::where('slug', 'epique')->first();
        $legendaire = RareteObjet::where('slug', 'legendaire')->first();
        
        $slotArme = SlotEquipement::where('slug', 'arme')->first();
        $slotTorse = SlotEquipement::where('slug', 'torse')->first();
        $slotAnneau = SlotEquipement::where('slug', 'anneau')->first();
        $slotTete = SlotEquipement::where('slug', 'tete')->first();
        $slotBottes = SlotEquipement::where('slug', 'bottes')->first();

        $objets = [
            // Armes
            [
                'name' => 'Épée en fer',
                'slug' => 'epee-fer',
                'rarete_id' => $commun->id,
                'slot_id' => $slotArme->id,
                'stackable' => false,
                'base_durability' => 100,
                'buy_price' => 50,
                'sell_price' => 25,
            ],
            [
                'name' => 'Épée enchantée',
                'slug' => 'epee-enchantee',
                'rarete_id' => $rare->id,
                'slot_id' => $slotArme->id,
                'stackable' => false,
                'base_durability' => 150,
                'buy_price' => 200,
                'sell_price' => 100,
            ],
            [
                'name' => 'Lame du dragon',
                'slug' => 'lame-dragon',
                'rarete_id' => $legendaire->id,
                'slot_id' => $slotArme->id,
                'stackable' => false,
                'base_durability' => 300,
                'buy_price' => 1000,
                'sell_price' => 500,
            ],
            
            // Armures
            [
                'name' => 'Plastron de cuir',
                'slug' => 'plastron-cuir',
                'rarete_id' => $commun->id,
                'slot_id' => $slotTorse->id,
                'stackable' => false,
                'base_durability' => 80,
                'buy_price' => 40,
                'sell_price' => 20,
            ],
            [
                'name' => 'Armure de mailles',
                'slug' => 'armure-mailles',
                'rarete_id' => $rare->id,
                'slot_id' => $slotTorse->id,
                'stackable' => false,
                'base_durability' => 120,
                'buy_price' => 150,
                'sell_price' => 75,
            ],
            
            // Anneaux
            [
                'name' => 'Anneau de force',
                'slug' => 'anneau-force',
                'rarete_id' => $rare->id,
                'slot_id' => $slotAnneau->id,
                'stackable' => false,
                'base_durability' => null, // Pas de durabilité
                'buy_price' => 100,
                'sell_price' => 50,
            ],
            [
                'name' => 'Anneau de sagesse',
                'slug' => 'anneau-sagesse',
                'rarete_id' => $epique->id,
                'slot_id' => $slotAnneau->id,
                'stackable' => false,
                'base_durability' => null,
                'buy_price' => 300,
                'sell_price' => 150,
            ],
            
            // Casques
            [
                'name' => 'Casque de garde',
                'slug' => 'casque-garde',
                'rarete_id' => $commun->id,
                'slot_id' => $slotTete->id,
                'stackable' => false,
                'base_durability' => 60,
                'buy_price' => 30,
                'sell_price' => 15,
            ],
            
            // Bottes
            [
                'name' => 'Bottes de voyage',
                'slug' => 'bottes-voyage',
                'rarete_id' => $commun->id,
                'slot_id' => $slotBottes->id,
                'stackable' => false,
                'base_durability' => 50,
                'buy_price' => 25,
                'sell_price' => 12,
            ],
            
            // Objets consommables (stackables)
            [
                'name' => 'Potion de soin',
                'slug' => 'potion-soin',
                'rarete_id' => $commun->id,
                'slot_id' => null, // Pas d\'équipement
                'stackable' => true,
                'base_durability' => null,
                'buy_price' => 10,
                'sell_price' => 5,
            ],
        ];

        foreach ($objets as $objet) {
            Objet::create($objet);
        }
    }
}