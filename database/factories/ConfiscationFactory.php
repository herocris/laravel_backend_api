<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Confiscation>
 */
class ConfiscationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-5 year', 'now')->format('Y-m-d'), //fecha entre 5 años atras y 1 año adelante
            'observation' => fake()->sentence(),
            'direction' => fake()->address(),
            'department' => fake()->word(),
            'municipality' => fake()->word(),
            'latitude' => fake()->randomFloat(6, 14.01, 14.99), //numero float con 6 decimales entre 14.01 y 14.99
            'length' => fake()->randomFloat(6, -86.01, -86.99),
        ];
    }
}
