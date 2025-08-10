<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SlotEquipement;

class SlotEquipementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            [
                'name' => 'Tête',
                'slug' => 'tete',
                'max_per_slot' => 1,
            ],
            [
                'name' => 'Torse',
                'slug' => 'torse',
                'max_per_slot' => 1,
            ],
            [
                'name' => 'Arme',
                'slug' => 'arme',
                'max_per_slot' => 1,
            ],
            [
                'name' => 'Anneau',
                'slug' => 'anneau',
                'max_per_slot' => 2,
            ],
            [
                'name' => 'Bottes',
                'slug' => 'bottes',
                'max_per_slot' => 1,
            ],
        ];

        foreach ($slots as $slot) {
            SlotEquipement::create($slot);
        }
    }
}