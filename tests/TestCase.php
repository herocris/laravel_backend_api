<?php

namespace Tests;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissionNames
     */
    protected function grantPermissions(User $user, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'api'));
        }
    }

    public function superAdminLogin(): User
    {
        return User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@yahoo.es',
            'password' => bcrypt('password'),
        ]);
    }
}
