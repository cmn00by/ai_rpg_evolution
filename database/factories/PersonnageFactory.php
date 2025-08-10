<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Classe;

class PersonnageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'classe_id' => Classe::inRandomOrder()->value('id') ?? Classe::factory(),
            'name'      => fake()->firstName(),
            'level'     => 1,
            'gold'      => fake()->numberBetween(0, 500),
            'is_active' => true,
        ];
    }
}