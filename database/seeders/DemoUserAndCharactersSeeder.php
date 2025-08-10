<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\Attribut;

class DemoUserAndCharactersSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un utilisateur de test
        $user = User::firstOrCreate(
            ['email' => 'demo@ai-rpg.local'],
            [
                'name' => 'Joueur Démo',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Récupérer toutes les classes
        $classes = Classe::all();
        $attributs = Attribut::where('type', '!=', 'derived')->get();

        // Créer un personnage par classe
        foreach ($classes as $classe) {
            $personnage = Personnage::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'classe_id' => $classe->id,
                ],
                [
                    'name' => $classe->name . ' de ' . $user->name,
                    'level' => 1,
                    'gold' => 100,
                ]
            );

            // Initialiser les attributs du personnage à 0 pour faciliter les tests
            foreach ($attributs as $attribut) {
                DB::table('personnage_attributs')->upsert([
                    'personnage_id' => $personnage->id,
                    'attribut_id' => $attribut->id,
                    'value' => 0,
                ], ['personnage_id', 'attribut_id']);
            }
        }
    }
}