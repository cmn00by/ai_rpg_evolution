<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttributFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Force','Vigueur','Dextérité','Intelligence','Chance']).' '.fake()->randomDigit();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'int',
            'default_value' => fake()->numberBetween(1,5),
            'min_value' => 0,
            'max_value' => 9999,
            'is_visible' => true,
            'order' => 0,
        ];
    }
}