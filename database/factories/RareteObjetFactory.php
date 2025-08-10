<?php

namespace Database\Factories;

use App\Models\RareteObjet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RareteObjet>
 */
class RareteObjetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RareteObjet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rarities = ['Commun', 'Rare', 'Épique', 'Légendaire', 'Mythique'];
        $name = $this->faker->randomElement($rarities);
        
        return [
            'name' => $name,
            'slug' => strtolower($name),
            'order' => $this->faker->numberBetween(1, 5),
            'color_hex' => $this->faker->hexColor(),
            'multiplier' => $this->faker->randomFloat(2, 0.5, 3.0),
        ];
    }

    /**
     * Create a common rarity.
     */
    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Commun',
            'slug' => 'commun',
            'order' => 1,
            'color_hex' => '#808080',
            'multiplier' => 1.0,
        ]);
    }

    /**
     * Create a rare rarity.
     */
    public function rare(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Rare',
            'slug' => 'rare',
            'order' => 2,
            'color_hex' => '#0070dd',
            'multiplier' => 1.5,
        ]);
    }

    /**
     * Create an epic rarity.
     */
    public function epic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Épique',
            'slug' => 'epique',
            'order' => 3,
            'color_hex' => '#a335ee',
            'multiplier' => 2.0,
        ]);
    }

    /**
     * Create a legendary rarity.
     */
    public function legendary(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Légendaire',
            'slug' => 'legendaire',
            'order' => 4,
            'color_hex' => '#ff8000',
            'multiplier' => 3.0,
        ]);
    }
}