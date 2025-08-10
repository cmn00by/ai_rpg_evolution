<?php

namespace Database\Factories;

use App\Models\AchatHistorique;
use App\Models\Personnage;
use App\Models\Boutique;
use App\Models\Objet;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchatHistoriqueFactory extends Factory
{
    protected $model = AchatHistorique::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $totalPrice = $unitPrice * $qty;
        $type = $this->faker->randomElement(['buy', 'sell']);
        
        return [
            'personnage_id' => Personnage::factory(),
            'boutique_id' => Boutique::factory(),
            'objet_id' => Objet::factory(),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'type' => $type,
            'meta_json' => [
                'solde_avant' => $this->faker->numberBetween(0, 10000),
                'solde_apres' => $this->faker->numberBetween(0, 10000),
                'taxes' => $type === 'buy' ? $totalPrice * 0.1 : 0,
                'remises' => $type === 'buy' ? $totalPrice * 0.05 : 0,
                'prix_base' => $unitPrice,
                'timestamp' => now()->toISOString()
            ],
            'ip_address' => $this->faker->ipv4()
        ];
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'buy',
        ]);
    }

    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sell',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }

    public function withMeta(array $meta): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_json' => array_merge($attributes['meta_json'] ?? [], $meta),
        ]);
    }
}