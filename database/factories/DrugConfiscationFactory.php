<?php

namespace Database\Factories;

use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DrugConfiscation>
 */
class DrugConfiscationFactory extends Factory
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
            'weight' => fake()->randomFloat(2, 0.01, 100.99),
            'confiscation_id' =>  Confiscation::all()->random()->id,
            'drug_id' => Drug::all()->random()->id,
            'drug_presentation_id' => DrugPresentation::all()->random()->id,
            'photo' => 'https://picsum.photos/150/150',
        ];
    }
}
