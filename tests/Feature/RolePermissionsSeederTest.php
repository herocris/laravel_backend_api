<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionsSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Tests\TestCase;

class RolePermissionsSeederTest extends TestCase
{
    public function test_administrator_receives_all_active_api_permissions(): void
    {
        $user = $this->createPermissionCatalog();
        Permission::factory()->create(['name' => 'web.only', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'deleted.permission', 'guard_name' => 'api'])->delete();

        $this->seed(RolePermissionsSeeder::class);
        $user->assignRole('administrador');

        $this->assertSame(49, $user->getAllPermissions()->count());
        foreach (Permission::where('guard_name', 'api')->get() as $permission) {
            $this->assertTrue($user->can($permission->name), $permission->name);
        }
        $this->assertFalse($user->can('web.only'));
        $this->assertFalse($user->can('deleted.permission'));
        $this->assertFalse($user->can('future.permission'));
    }

    /**
     * @return array<string, array{string, list<string>, int}>
     */
    public static function businessRoles(): array
    {
        return [
            'analyst' => ['analista', ['view'], 11],
            'operator' => ['operador', ['view', 'create', 'update'], 27],
        ];
    }

    /**
     * @param  list<string>  $allowedActions
     */
    #[DataProvider('businessRoles')]
    public function test_business_roles_have_only_their_allowed_operations(
        string $roleName,
        array $allowedActions,
        int $permissionCount,
    ): void {
        $user = $this->createPermissionCatalog();

        $this->seed(RolePermissionsSeeder::class);
        $user->assignRole($roleName);

        $this->assertSame($permissionCount, $user->getAllPermissions()->count());
        foreach (['drug', 'drugPresentation', 'weapon', 'ammunition', 'confiscation', 'drugConfiscation', 'weaponConfiscation', 'ammunitionConfiscation'] as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $this->assertSame(in_array($action, $allowedActions, true), $user->can($module.'.'.$action), $module.'.'.$action);
            }
        }
        foreach (['dashboard.view', 'graph.view', 'map.view'] as $permission) {
            $this->assertTrue($user->can($permission), $permission);
        }
        foreach (['user', 'role', 'permission'] as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $this->assertFalse($user->can($module.'.'.$action), $module.'.'.$action);
            }
        }
        $this->assertFalse($user->can('activity.view'));
        $this->assertFalse($user->can('unrelated.permission'));
    }

    public function test_repeated_runs_preserve_users_other_roles_and_direct_permissions(): void
    {
        $user = $this->createPermissionCatalog();
        $otherRole = Role::factory()->create(['name' => 'external', 'guard_name' => 'api']);
        $otherRole->givePermissionTo('unrelated.permission');
        $user->assignRole('operador');
        $user->givePermissionTo('unrelated.permission');
        Role::findByName('operador', 'api')->givePermissionTo('user.delete');

        $this->seed(RolePermissionsSeeder::class);
        $initialAssignments = DB::table('role_has_permissions')->count();
        $this->seed(RolePermissionsSeeder::class);

        $this->assertSame($initialAssignments, DB::table('role_has_permissions')->count());
        $this->assertDatabaseCount('permissions', 49);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(['operador'], $user->fresh()->getRoleNames()->all());
        $this->assertTrue($user->fresh()->hasDirectPermission('unrelated.permission'));
        $this->assertTrue($otherRole->fresh()->hasPermissionTo('unrelated.permission'));
        $this->assertFalse($user->fresh()->can('user.delete'));
    }

    public function test_missing_required_permissions_roll_back_all_role_changes(): void
    {
        $this->createPermissionCatalog();
        $administrator = Role::findByName('administrador', 'api');
        $administrator->givePermissionTo('unrelated.permission');
        $permission = Permission::findByName('unrelated.permission', 'api');
        Permission::findByName('drug.create', 'api')->delete();
        $this->expectException(PermissionDoesNotExist::class);

        try {
            $this->seed(RolePermissionsSeeder::class);
        } finally {
            $this->assertDatabaseCount('role_has_permissions', 1);
            $this->assertDatabaseHas('role_has_permissions', [
                'role_id' => $administrator->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    private function createPermissionCatalog(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        foreach (['user', 'role', 'permission', 'drug', 'drugPresentation', 'weapon', 'ammunition', 'confiscation', 'drugConfiscation', 'weaponConfiscation', 'ammunitionConfiscation'] as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::factory()->create(['name' => $module.'.'.$action, 'guard_name' => 'api']);
            }
        }
        foreach (['dashboard.view', 'graph.view', 'map.view', 'activity.view', 'unrelated.permission'] as $permission) {
            Permission::factory()->create(['name' => $permission, 'guard_name' => 'api']);
        }
        foreach (['administrador', 'operador', 'analista'] as $role) {
            Role::factory()->create(['name' => $role, 'guard_name' => 'api']);
        }

        return $user;
    }
}
