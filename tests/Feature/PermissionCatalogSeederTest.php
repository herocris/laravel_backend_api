<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionCatalogSeederTest extends TestCase
{
    public function test_initializes_the_catalog_and_role_permissions_without_a_signed_in_user(): void
    {
        $this->seed(PermissionCatalogSeeder::class);

        $this->assertDatabaseCount('permissions', 48);
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(['api'], Permission::distinct()->pluck('guard_name')->all());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'Sistema', 'event' => 'created']);

        foreach (['administrador' => 48, 'operador' => 27, 'analista' => 11] as $roleName => $count) {
            $user = User::factory()->create();
            $user->assignRole($roleName);

            $this->assertSame($count, $user->getAllPermissions()->count());
            $this->assertTrue($user->can('confiscation.view'));
            $this->assertSame($roleName === 'administrador', $user->can('user.update'));
            $this->assertSame($roleName !== 'analista', $user->can('drug.create'));
            $this->assertFalse($user->can('future.permission'));
        }
    }

    public function test_catalog_covers_every_protected_route(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $protectedRoutes = 0;

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (str_starts_with($middleware, 'can:')) {
                    $permission = substr($middleware, strlen('can:'));
                    $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'api', 'deleted_at' => null]);
                    $protectedRoutes++;
                }
            }
        }

        $this->assertSame(88, $protectedRoutes);
    }

    public function test_repeated_runs_preserve_existing_records_ids_and_assignments(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'external.view']);
        $role = Role::factory()->create(['name' => 'external']);
        $operator = Role::factory()->create(['name' => 'operador']);
        $role->givePermissionTo($permission);
        $operator->givePermissionTo($permission);
        $user->assignRole([$role, $operator]);
        $user->givePermissionTo($permission);
        $webRole = Role::factory()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $webPermission = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'web']);
        $webRole->givePermissionTo($webPermission);
        $deletedPermission = Permission::factory()->create(['name' => 'external.deleted']);
        $deletedPermission->delete();
        $deletedRole = Role::factory()->create(['name' => 'external.deleted']);
        $deletedRole->delete();

        $this->seed(PermissionCatalogSeeder::class);
        $permissionIds = Permission::withTrashed()->orderBy('id')->pluck('id')->all();
        $roleIds = Role::withTrashed()->orderBy('id')->pluck('id')->all();
        $auditCount = DB::table('activity_log')->count();
        $pivotCount = DB::table('role_has_permissions')->count();
        $this->seed(PermissionCatalogSeeder::class);

        $this->assertSame($permissionIds, Permission::withTrashed()->orderBy('id')->pluck('id')->all());
        $this->assertSame($roleIds, Role::withTrashed()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->assertDatabaseCount('role_has_permissions', $pivotCount);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('model_has_roles', 2);
        $this->assertTrue($user->fresh()->hasDirectPermission($permission));
        $this->assertTrue($operator->fresh()->hasPermissionTo($permission));
        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
        $this->assertSame(1, $webRole->fresh()->permissions()->count());
        $administrator = Role::findByName('administrador', 'api');
        $this->assertTrue($administrator->hasPermissionTo($permission));
        $this->assertFalse($administrator->permissions()->whereKey($webPermission->id)->exists());
        $this->assertFalse($administrator->permissions()->whereKey($deletedPermission->id)->exists());
        $this->assertSoftDeleted($deletedPermission);
        $this->assertSoftDeleted($deletedRole);
    }

    public function test_gate_excludes_deleted_api_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $this->seed(PermissionCatalogSeeder::class);
        $user->assignRole('analista');
        $user->givePermissionTo('drug.create');

        Role::findByName('analista', 'api')->delete();
        Permission::findByName('drug.create', 'api')->delete();

        $this->assertFalse($user->fresh()->can('confiscation.view'));
        $this->assertFalse($user->fresh()->can('drug.create'));
    }

    /**
     * @return array<string, array{class-string<Permission>|class-string<Role>, string}>
     */
    public static function deletedCatalogEntries(): array
    {
        return [
            'permission' => [Permission::class, 'graph.view'],
            'role' => [Role::class, 'analista'],
        ];
    }

    #[DataProvider('deletedCatalogEntries')]
    public function test_deleted_catalog_entries_abort_without_restoring_or_partially_creating_data(string $modelClass, string $name): void
    {
        $record = $modelClass::factory()->create(['name' => $name]);
        $record->delete();
        $auditCount = DB::table('activity_log')->count();
        $this->expectException(DomainException::class);

        try {
            $this->seed(PermissionCatalogSeeder::class);
        } finally {
            $this->assertSoftDeleted($record);
            $this->assertDatabaseCount('permissions', $modelClass === Permission::class ? 1 : 0);
            $this->assertDatabaseCount('roles', $modelClass === Role::class ? 1 : 0);
            $this->assertDatabaseCount('role_has_permissions', 0);
            $this->assertDatabaseCount('activity_log', $auditCount);
        }
    }
}
