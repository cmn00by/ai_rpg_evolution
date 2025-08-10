<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $classes = [
            'Guerrier' => ['Force'=>8,'Vigueur'=>7,'Dextérité'=>4,'Intelligence'=>2,'Chance'=>1],
            'Voleur' => ['Force'=>4,'Vigueur'=>5,'Dextérité'=>8,'Intelligence'=>6,'Chance'=>7],
            'Mage' => ['Force'=>2,'Vigueur'=>3,'Dextérité'=>5,'Intelligence'=>9,'Chance'=>3],
            'Ranger' => ['Force'=>6,'Vigueur'=>6,'Dextérité'=>7,'Intelligence'=>5,'Chance'=>4],
        ];

        foreach ($classes as $className => $attributes) {
            $classData = [
                'name' => $className,
                'slug' => Str::slug($className),
                'base_level' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            DB::table('classes')->upsert([$classData], ['slug']);
            
            // Récupérer l'ID de la classe
            $classeId = DB::table('classes')->where('slug', Str::slug($className))->value('id');
            
            // Insérer les relations classe_attributs avec base_value
            foreach ($attributes as $attributName => $baseValue) {
                $attributId = DB::table('attributs')->where('name', $attributName)->value('id');
                if ($attributId) {
                    DB::table('classe_attributs')->upsert([
                        'classe_id' => $classeId,
                        'attribut_id' => $attributId,
                        'base_value' => $baseValue,
                    ], ['classe_id', 'attribut_id']);
                }
            }
        }
    }
}