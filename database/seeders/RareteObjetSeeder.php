<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RareteObjet;

class RareteObjetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $raretes = [
            [
                'name' => 'Commun',
                'slug' => 'commun',
                'order' => 1,
                'color_hex' => '#9CA3AF', // Gris
                'multiplier' => 1.0,
            ],
            [
                'name' => 'Rare',
                'slug' => 'rare',
                'order' => 2,
                'color_hex' => '#3B82F6', // Bleu
                'multiplier' => 1.5,
            ],
            [
                'name' => 'Épique',
                'slug' => 'epique',
                'order' => 3,
                'color_hex' => '#8B5CF6', // Violet
                'multiplier' => 2.0,
            ],
            [
                'name' => 'Légendaire',
                'slug' => 'legendaire',
                'order' => 4,
                'color_hex' => '#F59E0B', // Orange
                'multiplier' => 3.0,
            ],
        ];

        foreach ($raretes as $rarete) {
            RareteObjet::create($rarete);
        }
    }
}