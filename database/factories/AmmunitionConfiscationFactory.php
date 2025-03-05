<?php

namespace Database\Factories;

use App\Models\Ammunition;
use App\Models\Confiscation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AmmunitionConfiscation>
 */
class AmmunitionConfiscationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(0, 100),
            'confiscation_id' => Confiscation::all()->random()->id,
            'ammunition_id' => Ammunition::all()->random()->id,
            'photo' => 'https://picsum.photos/150/150',
        ];
    }
}
