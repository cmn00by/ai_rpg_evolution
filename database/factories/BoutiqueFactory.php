<?php

namespace Database\Factories;

use App\Models\Boutique;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoutiqueFactory extends Factory
{
    protected $model = Boutique::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'config_json' => [
                'allowed_slots' => ['weapon', 'armor', 'accessory'],
                'allowed_rarities' => [1, 2, 3, 4, 5],
                'tax_rate' => $this->faker->numberBetween(0, 15),
                'discount_rate' => $this->faker->numberBetween(0, 10),
                'limits' => [
                    'max_per_transaction' => $this->faker->numberBetween(5, 20),
                    'max_per_day' => $this->faker->numberBetween(50, 200)
                ]
            ],
            'is_active' => true
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBlacklist(array $objetIds): static
    {
        return $this->state(fn (array $attributes) => [
            'config_json' => array_merge($attributes['config_json'] ?? [], [
                'blacklist' => $objetIds
            ])
        ]);
    }

    public function withWhitelist(array $objetIds): static
    {
        return $this->state(fn (array $attributes) => [
            'config_json' => array_merge($attributes['config_json'] ?? [], [
                'whitelist' => $objetIds
            ])
        ]);
    }
}