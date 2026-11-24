<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\AccessControl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PrivilegeDelegationTest extends TestCase
{
    /** @return iterable<string, array{string, bool, bool}> */
    public static function delegations(): iterable
    {
        foreach (['user', 'role'] as $resource) {
            foreach ([false, true] as $update) {
                foreach ([false, true] as $viaRole) {
                    if ($viaRole && $resource !== 'user') {
                        continue;
                    }
                    yield $resource.($update ? ' update' : ' create').($viaRole ? ' inherited' : ' direct') => [$resource, $update, $viaRole];
                }
            }
        }
    }

    #[DataProvider('delegations')]
    public function test_unowned_delegation_returns_403_without_changing_attributes_assignments_or_audit(string $resource, bool $update, bool $viaRole): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.($update ? '.update' : '.create')]);
        $permission = Permission::factory()->create(['name' => 'drug.delete']);
        $role = Role::factory()->create(['name' => 'administrador']);
        $role->givePermissionTo($permission);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $before = $target->newQueryWithoutScopes()->findOrFail($target->id)->getAttributes();
        $auditCount = DB::table('activity_log')->count();
        $this->actingAs($actor, 'api');

        $this->json($update ? 'PATCH' : 'POST', '/api/'.$resource.($update ? '/'.$target->id : ''), [
            ...$this->payload(),
            ...($viaRole ? ['roles' => [(string) $role->id]] : ['permisos' => [(string) $permission->id]]),
        ])->assertForbidden()->assertJsonPath('code', 403)->assertJsonStructure(['error', 'code']);

        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertDatabaseMissing($target->getTable(), ['name' => 'Delegated']);
        $this->assertSame([], $target->fresh()->permissions()->pluck('permissions.id')->all());
        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    #[DataProvider('delegations')]
    public function test_owned_delegation_preserves_numeric_ids_and_existing_success_contract(string $resource, bool $update, bool $viaRole): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.($update ? '.update' : '.create')]);
        $permission = Permission::factory()->create(['name' => 'drug.view']);
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);
        $actor->assignRole($role);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();

        $response = $this->actingAs($actor, 'api')->json($update ? 'PATCH' : 'POST', '/api/'.$resource.($update ? '/'.$target->id : ''), [
            ...$this->payload(),
            ...($viaRole ? ['roles' => [(string) $role->id]] : ['permisos' => [(string) $permission->id]]),
        ])->assertOk()->assertJsonPath('nombre', 'Delegated');

        $created = ($resource === 'user' ? User::class : Role::class)::findOrFail($response->json('identificador'));
        $this->assertTrue($created->hasPermissionTo($permission));
        $response->assertJsonPath($viaRole ? 'roles' : 'permisos', [$viaRole ? $role->id : $permission->id]);
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function protectedTargets(): iterable
    {
        foreach (['user', 'role'] as $resource) {
            foreach (['update', 'clear', 'delete', 'restore'] as $operation) {
                foreach ([false, true] as $dormant) {
                    yield "$resource $operation ".($dormant ? 'dormant' : 'active') => [$resource, $operation, $dormant];
                }
            }
        }
    }

    #[DataProvider('protectedTargets')]
    public function test_more_privileged_targets_cannot_be_taken_over_or_changed(string $resource, string $operation, bool $dormant): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.($operation === 'delete' ? '.delete' : '.update')]);
        $permission = Permission::factory()->create(['name' => 'drug.delete']);
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);
        $target = $resource === 'user' ? User::factory()->create() : $role;
        if ($resource === 'user') {
            $target->assignRole($role);
        }
        if ($dormant) {
            if ($resource === 'user' || $operation === 'restore') {
                $role->delete();
            }
            $permission->delete();
        }
        if ($operation === 'restore') {
            $target->delete();
        }
        $before = $target->newQueryWithoutScopes()->findOrFail($target->id)->getAttributes();
        $auditCount = DB::table('activity_log')->count();
        $url = '/api/'.$resource.($operation === 'restore' ? '/restore' : '').'/'.$target->id;
        $method = match ($operation) {
            'delete' => 'DELETE', 'restore' => 'POST', default => 'PATCH'
        };

        $this->actingAs($actor, 'api')->json($method, $url, [
            ...$this->payload(), ...($operation === 'clear' ? ['roles' => [], 'permisos' => []] : []),
        ])->assertForbidden()->assertJsonPath('code', 403);

        $this->assertSame($before, ($resource === 'user' ? User::class : Role::class)::withTrashed()->findOrFail($target->id)->getAttributes());
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->assertDatabaseHas('role_has_permissions', ['role_id' => $role->id, 'permission_id' => $permission->id]);
    }

    #[TestWith(['roles'])]
    #[TestWith(['direct'])]
    public function test_permission_rename_cannot_grant_a_new_capability_through_existing_assignments(string $assignment): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['permission.update', 'custom.original']);
        $permission = Permission::findByName('custom.original', 'api');
        $target = User::factory()->create();
        if ($assignment === 'roles') {
            $role = Role::factory()->create();
            $role->givePermissionTo($permission);
            $target->assignRole($role);
            $role->delete();
        } else {
            $target->givePermissionTo($permission);
        }
        $auditCount = DB::table('activity_log')->count();

        $this->actingAs($actor, 'api')->patchJson('/api/permission/'.$permission->id, ['nombre' => 'user.update'])
            ->assertForbidden()->assertJsonPath('code', 403);

        $this->assertSame('custom.original', $permission->fresh()->name);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    #[TestWith(['restore'])]
    public function test_permission_mutations_check_privileges_of_affected_users(string $operation): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['permission.'.($operation === 'delete' ? 'delete' : 'update'), 'custom.shared']);
        $target = User::factory()->create();
        $this->grantPermissions($target, ['custom.shared', 'drug.delete']);
        $permission = Permission::findByName('custom.shared', 'api');
        if ($operation === 'restore') {
            $permission->delete();
        }
        $url = '/api/permission'.($operation === 'restore' ? '/restore' : '').'/'.$permission->id;
        $before = $permission->newQueryWithoutScopes()->findOrFail($permission->id)->getAttributes();
        $auditCount = DB::table('activity_log')->count();

        $this->actingAs($actor, 'api')->json(match ($operation) {
            'delete' => 'DELETE', 'restore' => 'POST', default => 'PATCH'
        }, $url, ['nombre' => $permission->name])
            ->assertForbidden()->assertJsonPath('code', 403);

        $this->assertSame($before, Permission::withTrashed()->findOrFail($permission->id)->getAttributes());
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_admin_role_name_does_not_allow_restoring_an_assigned_permission_the_actor_cannot_use(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['permission.update']);
        $role = Role::factory()->create(['name' => 'administrador']);
        $actor->assignRole($role);
        $permission = Permission::factory()->create(['name' => 'drug.delete']);
        $role->givePermissionTo($permission);
        $permission->delete();

        $this->actingAs($actor, 'api')->postJson('/api/permission/restore/'.$permission->id)->assertForbidden();

        $this->assertSoftDeleted($permission);
    }

    public function test_permission_ids_from_another_guard_do_not_satisfy_ownership(): void
    {
        $actor = User::factory()->create();
        $web = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'web']);
        $api = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'api']);
        DB::table('model_has_permissions')->insert(['permission_id' => $web->id, 'model_type' => User::class, 'model_id' => $actor->id]);

        $this->expectException(AuthorizationException::class);

        (new AccessControl)->authorizePermissions($actor, [$api->id]);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_revocation_is_respected_even_with_preloaded_actor_relations(bool $viaRole): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['role.create', 'drug.view']);
        $permission = Permission::findByName('drug.view', 'api');
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);
        if ($viaRole) {
            $actor->revokePermissionTo($permission);
            $actor->assignRole($role);
        }
        $actor->load(['permissions', 'roles.permissions']);
        if ($viaRole) {
            $role->fresh()->syncPermissions([]);
        } else {
            $actor->fresh()->revokePermissionTo($permission);
        }

        $this->actingAs($actor, 'api')->postJson('/api/role', ['nombre' => 'Denied', 'permisos' => [$permission->id]])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Denied']);
    }

    #[TestWith(['user', false])]
    #[TestWith(['user', true])]
    #[TestWith(['role', false])]
    #[TestWith(['role', true])]
    public function test_owned_targets_can_be_deleted_or_restored_with_existing_json_contract(string $resource, bool $restore): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.($restore ? '.update' : '.delete'), 'drug.view']);
        $permission = Permission::findByName('drug.view', 'api');
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $target->givePermissionTo($permission);
        if ($restore) {
            $target->delete();
        }

        $this->actingAs($actor, 'api')->json($restore ? 'POST' : 'DELETE', '/api/'.$resource.($restore ? '/restore' : '').'/'.$target->id)
            ->assertOk()->assertJsonPath('identificador', $target->id)->assertJsonPath('permisos', [$permission->id]);

        if ($restore) {
            $this->assertNotSoftDeleted($target);
        } else {
            $this->assertSoftDeleted($target);
        }
    }

    public function test_a_user_with_unowned_direct_permissions_cannot_be_taken_over(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.update']);
        $target = User::factory()->create();
        $this->grantPermissions($target, ['drug.delete']);
        $originalEmail = $target->email;

        $this->actingAs($actor, 'api')->patchJson('/api/user/'.$target->id, $this->payload())->assertForbidden();

        $this->assertSame($originalEmail, $target->fresh()->email);
    }

    public function test_active_roles_with_dormant_permissions_cannot_be_assigned(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.create']);
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->givePermissionTo($permission);
        $permission->delete();

        $this->actingAs($actor, 'api')->postJson('/api/user', [...$this->payload(), 'roles' => [$role->id]])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'delegated@example.test']);
    }

    /** @return array{nombre: string, correo: string, password: string} */
    private function payload(): array
    {
        return ['nombre' => 'Delegated', 'correo' => 'delegated@example.test', 'password' => 'test-password'];
    }
}
