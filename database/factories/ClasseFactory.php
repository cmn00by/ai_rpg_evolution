<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClasseFactory extends Factory
{
    public function definition(): array
    {
        $classes = ['Guerrier', 'Voleur', 'Mage', 'Ranger', 'Paladin', 'Archer', 'Assassin', 'Prêtre'];
        $name = $this->faker->randomElement($classes) . ' ' . $this->faker->randomNumber(3);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'base_level' => 1,
        ];
    }
}