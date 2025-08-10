<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ObjetAttribut;
use App\Models\Objet;
use App\Models\Attribut;

class ObjetAttributSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les objets
        $epeeFer = Objet::where('slug', 'epee-fer')->first();
        $epeeEnchantee = Objet::where('slug', 'epee-enchantee')->first();
        $lameDragon = Objet::where('slug', 'lame-dragon')->first();
        $plastronCuir = Objet::where('slug', 'plastron-cuir')->first();
        $armureMailles = Objet::where('slug', 'armure-mailles')->first();
        $anneauForce = Objet::where('slug', 'anneau-force')->first();
        $anneauSagesse = Objet::where('slug', 'anneau-sagesse')->first();
        $casqueGarde = Objet::where('slug', 'casque-garde')->first();
        $bottesVoyage = Objet::where('slug', 'bottes-voyage')->first();
        
        // Récupérer les attributs
        $force = Attribut::where('slug', 'force')->first();
        $vigueur = Attribut::where('slug', 'vigueur')->first();
        $dexterite = Attribut::where('slug', 'dexterite')->first();
        $intelligence = Attribut::where('slug', 'intelligence')->first();
        $chance = Attribut::where('slug', 'chance')->first();

        $modificateurs = [
            // Épée en fer - +2 Force
            [
                'objet_id' => $epeeFer->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'flat',
                'modifier_value' => 2,
            ],
            
            // Épée enchantée - +4 Force, +5% Force
            [
                'objet_id' => $epeeEnchantee->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'flat',
                'modifier_value' => 4,
            ],
            [
                'objet_id' => $epeeEnchantee->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'percent',
                'modifier_value' => 5,
            ],
            
            // Lame du dragon - +10 Force, +15% Force, +3 Dextérité
            [
                'objet_id' => $lameDragon->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'flat',
                'modifier_value' => 10,
            ],
            [
                'objet_id' => $lameDragon->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'percent',
                'modifier_value' => 15,
            ],
            [
                'objet_id' => $lameDragon->id,
                'attribut_id' => $dexterite->id,
                'modifier_type' => 'flat',
                'modifier_value' => 3,
            ],
            
            // Plastron de cuir - +1 Vigueur
            [
                'objet_id' => $plastronCuir->id,
                'attribut_id' => $vigueur->id,
                'modifier_type' => 'flat',
                'modifier_value' => 1,
            ],
            
            // Armure de mailles - +3 Vigueur, +2 Force
            [
                'objet_id' => $armureMailles->id,
                'attribut_id' => $vigueur->id,
                'modifier_type' => 'flat',
                'modifier_value' => 3,
            ],
            [
                'objet_id' => $armureMailles->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'flat',
                'modifier_value' => 2,
            ],
            
            // Anneau de force - +3 Force, +10% Force
            [
                'objet_id' => $anneauForce->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'flat',
                'modifier_value' => 3,
            ],
            [
                'objet_id' => $anneauForce->id,
                'attribut_id' => $force->id,
                'modifier_type' => 'percent',
                'modifier_value' => 10,
            ],
            
            // Anneau de sagesse - +5 Intelligence, +2 Chance
            [
                'objet_id' => $anneauSagesse->id,
                'attribut_id' => $intelligence->id,
                'modifier_type' => 'flat',
                'modifier_value' => 5,
            ],
            [
                'objet_id' => $anneauSagesse->id,
                'attribut_id' => $chance->id,
                'modifier_type' => 'flat',
                'modifier_value' => 2,
            ],
            
            // Casque de garde - +1 Vigueur
            [
                'objet_id' => $casqueGarde->id,
                'attribut_id' => $vigueur->id,
                'modifier_type' => 'flat',
                'modifier_value' => 1,
            ],
            
            // Bottes de voyage - +2 Dextérité
            [
                'objet_id' => $bottesVoyage->id,
                'attribut_id' => $dexterite->id,
                'modifier_type' => 'flat',
                'modifier_value' => 2,
            ],
        ];

        foreach ($modificateurs as $modificateur) {
            ObjetAttribut::create($modificateur);
        }
    }
}