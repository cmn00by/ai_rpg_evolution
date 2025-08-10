<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ordre important : Attributes → Classes → Demo → Objets
        $this->call([
            AttributeSeeder::class,
            ClassSeeder::class,
            DemoUserAndCharactersSeeder::class,
            RareteObjetSeeder::class,
            SlotEquipementSeeder::class,
            ObjetSeeder::class,
            ObjetAttributSeeder::class,
        ]);
    }
}
