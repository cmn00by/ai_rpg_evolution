<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = collect([
            ['Force','int',5],
            ['Vigueur','int',5],
            ['Dextérité','int',5],
            ['Intelligence','int',5],
            ['Chance','int',0],
            ['PV max','derived',null],    // ex. calculé plus tard
        ])->map(function($a) use($now){
            [$name,$type,$def] = $a;
            return [
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'default_value' => $def,
                'min_value' => 0,
                'max_value' => 999999,
                'is_visible' => $type !== 'derived', // masquer les dérivés par défaut
                'order' => 0,
                'created_at'=>$now,'updated_at'=>$now,
            ];
        })->all();

        DB::table('attributs')->upsert($rows, ['slug']);
    }
}