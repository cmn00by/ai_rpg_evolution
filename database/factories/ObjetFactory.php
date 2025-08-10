<?php

namespace Database\Factories;

use App\Models\Objet;
use App\Models\RareteObjet;
use App\Models\SlotEquipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Objet>
 */
class ObjetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Objet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug(),
            'rarete_id' => RareteObjet::factory(),
            'slot_id' => SlotEquipement::factory(),
            'stackable' => $this->faker->boolean(30), // 30% chance d'être stackable
            'base_durability' => $this->faker->numberBetween(10, 100),
            'buy_price' => $this->faker->randomFloat(2, 1, 1000),
            'sell_price' => $this->faker->randomFloat(2, 1, 500),
        ];
    }

    /**
     * Indicate that the object is stackable.
     */
    public function stackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'stackable' => true,
        ]);
    }

    /**
     * Indicate that the object is not stackable.
     */
    public function notStackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'stackable' => false,
        ]);
    }

    /**
     * Set a specific rarity.
     */
    public function withRarity(int $rareteId): static
    {
        return $this->state(fn (array $attributes) => [
            'rarete_id' => $rareteId,
        ]);
    }

    /**
     * Set a specific slot.
     */
    public function withSlot(int $slotId): static
    {
        return $this->state(fn (array $attributes) => [
            'slot_id' => $slotId,
        ]);
    }

    /**
     * Set specific prices.
     */
    public function withPrices(float $buyPrice, float $sellPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'buy_price' => $buyPrice,
            'sell_price' => $sellPrice,
        ]);
    }
}