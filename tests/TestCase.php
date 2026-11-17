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
     * Reproduce una petición independiente sin conservar el usuario ni el JWT anterior.
     */
    protected function resetJwtAuthentication(): void
    {
        app('auth')->forgetGuards();

        foreach ([
            'tymon.jwt', 'tymon.jwt.auth', 'tymon.jwt.manager',
            'tymon.jwt.validators.payload', 'tymon.jwt.claim.factory', 'tymon.jwt.payload.factory',
        ] as $binding) {
            app()->forgetInstance($binding);
        }
    }

    /**
     * @param  list<string>  $permissionNames
     */
    protected function grantPermissions(User $user, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'api'));
        }
    }

    protected function createUnprivilegedUser(): User
    {
        return User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@yahoo.es',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Alias histórico: crea un usuario, sin autenticarlo ni conceder permisos.
     *
     * @deprecated Use createUnprivilegedUser() y conceda los permisos de cada prueba explícitamente.
     */
    public function superAdminLogin(): User
    {
        return $this->createUnprivilegedUser();
    }
}
