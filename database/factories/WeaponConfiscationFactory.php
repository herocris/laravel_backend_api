<?php

namespace Database\Factories;

use App\Models\Confiscation;
use App\Models\Weapon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WeaponConfiscation>
 */
class WeaponConfiscationFactory extends Factory
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
            'weapon_id' => Weapon::all()->random()->id,
            'photo' => 'https://picsum.photos/150/150',
        ];
    }
}
