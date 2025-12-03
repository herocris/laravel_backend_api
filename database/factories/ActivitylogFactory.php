<?php

namespace Database\Factories;

use App\Models\Activitylog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activitylog>
 */
class ActivitylogFactory extends Factory
{
    protected $model = Activitylog::class;

    public function definition(): array
    {
        return [
            'log_name' => $this->faker->randomElement(['default', 'system', 'auth']),
            'description' => $this->faker->sentence(),
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [
                'ip' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted', null]),
            'batch_uuid' => $this->faker->boolean(30) ? Str::uuid()->toString() : null,
        ];
    }
}
