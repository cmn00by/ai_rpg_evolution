<?php

namespace Database\Factories;

use App\Models\BoutiqueItem;
use App\Models\Boutique;
use App\Models\Objet;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoutiqueItemFactory extends Factory
{
    protected $model = BoutiqueItem::class;

    public function definition(): array
    {
        return [
            'boutique_id' => Boutique::factory(),
            'objet_id' => Objet::factory(),
            'stock' => $this->faker->numberBetween(0, 50),
            'price_override' => null,
            'allow_buy' => true,
            'allow_sell' => $this->faker->boolean(70),
            'rarity_min' => null,
            'rarity_max' => null,
            'restock_rule' => null,
            'last_restock' => null
        ];
    }

    public function withPriceOverride(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_override' => $price,
        ]);
    }

    public function sellOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_buy' => false,
            'allow_sell' => true,
        ]);
    }

    public function buyOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_buy' => true,
            'allow_sell' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function withRestockRule(array $rule = null): static
    {
        $defaultRule = [
            'freq' => 'daily',
            'at' => '03:00',
            'qty' => 10,
            'cap' => 50
        ];

        return $this->state(fn (array $attributes) => [
            'restock_rule' => $rule ?? $defaultRule,
            'last_restock' => $this->faker->dateTimeBetween('-1 week', 'now')
        ]);
    }

    public function withRarityRange(int $min, int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity_min' => $min,
            'rarity_max' => $max,
        ]);
    }
}