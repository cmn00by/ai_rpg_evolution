<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Inventaire;
use App\Models\Objet;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventaireItem>
 */
class InventaireItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventaire_id' => Inventaire::factory(),
            'objet_id' => Objet::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'durability' => fake()->numberBetween(50, 100),
            'is_equipped' => false,
        ];
    }
}