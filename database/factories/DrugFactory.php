<?php

namespace Database\Factories;

use App\Models\DrugPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Droga>
 */
class DrugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => "droga " . fake()->unique()->word(),
            //'drug_presentation_id' => DrugPresentation::all()->random()->id,
            'logo' => 'https://picsum.photos/150/150',

        ];
    }
}
