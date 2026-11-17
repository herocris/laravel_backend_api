<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssignAdministratorCommandTest extends TestCase
{
    public function test_assigns_only_the_designated_user_and_preserves_assignments_on_repeated_runs(): void
    {
        $firstUser = User::factory()->create();
        $designatedUser = User::factory()->create();
        $this->seed(PermissionCatalogSeeder::class);
        $designatedUser->assignRole('analista');
        $permission = Permission::factory()->create(['name' => 'external.view']);
        $designatedUser->givePermissionTo($permission);

        $this->artisan('authorization:assign-administrator', ['user' => (string) $designatedUser->id])
            ->expectsOutput('Rol administrador (api) asignado al usuario ID '.$designatedUser->id.'.')
            ->assertExitCode(0);
        $this->artisan('authorization:assign-administrator', ['user' => (string) $designatedUser->id])
            ->assertExitCode(0);

        $this->assertTrue($designatedUser->fresh()->hasRole('administrador', 'api'));
        $this->assertTrue($designatedUser->fresh()->hasRole('analista', 'api'));
        $this->assertTrue($designatedUser->fresh()->hasDirectPermission($permission));
        $this->assertTrue($designatedUser->fresh()->can('user.update'));
        $this->assertFalse($firstUser->fresh()->can('user.update'));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('model_has_roles', 2);
        $this->assertDatabaseCount('model_has_permissions', 1);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidUsers(): array
    {
        return [
            'name is not an id' => ['cris', 'Indica el ID numérico exacto de un usuario activo.'],
            'missing id' => ['999999', 'No existe un usuario activo con ese ID.'],
            'deleted user' => ['deleted', 'No existe un usuario activo con ese ID.'],
        ];
    }

    #[DataProvider('invalidUsers')]
    public function test_rejects_invalid_or_deleted_users_without_assigning_anyone(string $userId, string $message): void
    {
        User::factory()->create();
        $deletedUser = User::factory()->create();
        $deletedUser->delete();
        $this->seed(PermissionCatalogSeeder::class);

        $this->artisan('authorization:assign-administrator', ['user' => $userId === 'deleted' ? (string) $deletedUser->id : $userId])
            ->expectsOutput($message)
            ->assertExitCode(1);

        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('users', 2);
        $this->assertSoftDeleted($deletedUser);
    }

    public function test_requires_a_configured_active_api_administrator_role(): void
    {
        $user = User::factory()->create();
        $webRole = Role::factory()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $apiRole = Role::factory()->create(['name' => 'administrador', 'guard_name' => 'api']);
        $apiRole->delete();

        $this->artisan('authorization:assign-administrator', ['user' => (string) $user->id])
            ->expectsOutput('Inicializa antes el catálogo con PermissionCatalogSeeder.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('permissions', 0);
        $this->assertModelExists($webRole);
        $this->assertSoftDeleted($apiRole);
    }

    public function test_rejects_an_administrator_role_without_permissions(): void
    {
        $user = User::factory()->create();
        Role::factory()->create(['name' => 'administrador', 'guard_name' => 'api']);

        $this->artisan('authorization:assign-administrator', ['user' => (string) $user->id])
            ->expectsOutput('Inicializa antes el catálogo con PermissionCatalogSeeder.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('model_has_roles', 0);
    }
}
