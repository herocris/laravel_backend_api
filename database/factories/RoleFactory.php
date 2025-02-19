<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->word();
        return [
            'name' => "role {$word}",
            'guard_name' => 'api',
        ];
    }

    public function givePermissionsToRole(): static
    {
        return $this->afterCreating(function (Role $role) {
            $role->givePermissionTo($this->PermissionsArray());
        });
    }

    private function PermissionsArray(): array
    {
        $permi = [];
        foreach (range(1, $this->faker->numberBetween(1, Permission::all()->count())) as $i) {
            $permi[] = Permission::inRandomOrder()->first()->name;
        }
        return $permi;
    }
}
