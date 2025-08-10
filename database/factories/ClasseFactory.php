<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClasseFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Guerrier','Voleur','Mage','Ranger']);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'base_level' => 1,
        ];
    }
}