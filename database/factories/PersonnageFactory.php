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
            'user_id'     => User::factory(),
            'classe_id'   => Classe::factory(),
            'name'        => $this->faker->firstName(),
            'level'       => 1,
            'gold'        => $this->faker->numberBetween(100, 1000),
        ];
    }
}