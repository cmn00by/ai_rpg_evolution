<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Personnage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventaire>
 */
class InventaireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personnage_id' => Personnage::factory(),
        ];
    }
}