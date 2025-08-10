<?php

namespace Database\Factories;

use App\Models\SlotEquipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlotEquipement>
 */
class SlotEquipementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SlotEquipement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slots = ['Arme', 'Casque', 'Armure', 'Gants', 'Bottes', 'Accessoire', 'Consommable'];
        $name = $this->faker->randomElement($slots);
        
        return [
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '_', $name)),
            'max_per_slot' => $this->faker->numberBetween(1, 2),
        ];
    }

    /**
     * Create a weapon slot.
     */
    public function weapon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Arme',
            'slug' => 'arme',
            'max_per_slot' => 1,
        ]);
    }

    /**
     * Create an armor slot.
     */
    public function armor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Armure',
            'slug' => 'armure',
            'max_per_slot' => 1,
        ]);
    }

    /**
     * Create a consumable slot.
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Consommable',
            'slug' => 'consommable',
            'max_per_slot' => 99,
        ]);
    }

    /**
     * Create an accessory slot.
     */
    public function accessory(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Accessoire',
            'slug' => 'accessoire',
            'max_per_slot' => 2,
        ]);
    }
}