<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class PrivilegeAssignmentTest extends TestCase
{
    #[TestWith(['permissions'])]
    #[TestWith(['permisos'])]
    public function test_public_registration_ignores_privileges(string $field): void
    {
        $permission = Permission::factory()->create(['name' => 'user.update']);
        $role = Role::factory()->create(['name' => 'administrador']);
        $role->givePermissionTo($permission);

        $this->postJson('/api/register', [
            'name' => 'Public User', 'email' => 'public@example.com', 'password' => 'password123',
            'roles' => [$role->id], $field => [$permission->id],
        ])->assertOk()->assertJsonPath('roles', [])->assertJsonPath('permissions', []);

        $user = User::where('email', 'public@example.com')->firstOrFail();
        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertFalse($user->can('user.update'));
    }

    #[TestWith(['user', 'POST'])]
    #[TestWith(['user', 'PUT'])]
    #[TestWith(['role', 'POST'])]
    #[TestWith(['role', 'PUT'])]
    public function test_ordinary_users_receive_403_without_changing_assignments(string $resource, string $method): void
    {
        $actor = User::factory()->create();
        $target = $resource === 'user' ? $actor : Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'user.update']);
        $this->actingAs($actor, 'api');

        $this->json($method, '/api/'.$resource.($method === 'PUT' ? '/'.$target->id : ''), [
            'nombre' => 'Escalated', 'correo' => 'new@example.com', 'password' => 'password123',
            'permisos' => [$permission->id],
        ])->assertForbidden();

        $this->assertDatabaseMissing($target->getTable(), ['name' => 'Escalated']);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('role_has_permissions', 0);
    }

    #[TestWith(['user'])]
    #[TestWith(['role'])]
    public function test_administrative_creation_assigns_numeric_string_ids(string $resource): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.'.create']);
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'drug.view']);
        $actor->givePermissionTo($permission);

        $response = $this->postJson('/api/'.$resource, [
            'nombre' => 'Created', 'correo' => 'created@example.com', 'password' => 'password123',
            'roles' => [(string) $role->id], 'permisos' => [(string) $permission->id],
        ])->assertCreated()->assertJsonPath('data.permisos', [$permission->id]);

        $model = $resource === 'user' ? User::class : Role::class;
        $created = $model::findOrFail($response->json('data.identificador'));
        $this->assertTrue($created->hasPermissionTo($permission));
        if ($resource === 'user') {
            $this->assertTrue($created->hasRole($role));
        } else {
            $this->assertSame('api', $created->guard_name);
        }
    }

    #[TestWith(['user', false, false])]
    #[TestWith(['user', true, false])]
    #[TestWith(['user', false, true])]
    #[TestWith(['user', true, true])]
    #[TestWith(['role', false, false])]
    #[TestWith(['role', false, true])]
    public function test_update_preserves_omitted_assignments_and_revokes_explicit_empty_lists(string $resource, bool $clearRoles, bool $clearPermissions): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.'.update']);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $target->givePermissionTo($permission);
        $actor->givePermissionTo($permission);
        if ($resource === 'user') {
            $target->assignRole($role);
        }
        $payload = ['nombre' => 'Updated', 'correo' => 'updated@example.com', 'password' => 'password123'];
        if ($clearRoles) {
            $payload['roles'] = [];
        }
        if ($clearPermissions) {
            $payload['permisos'] = [];
        }

        $response = $this->patchJson('/api/'.$resource.'/'.$target->id, $payload)->assertOk()
            ->assertJsonPath('data.permisos', $clearPermissions ? [] : [$permission->id]);

        $this->assertSame($clearPermissions ? [] : [$permission->id], $target->fresh()->permissions->pluck('id')->all());
        if ($resource === 'user') {
            $response->assertJsonPath('data.roles', $clearRoles ? [] : [$role->id]);
            $this->assertSame($clearRoles ? [] : [$role->id], $target->fresh()->roles->pluck('id')->all());
        }
        $this->assertDatabaseHas($target->getTable(), ['id' => $target->id, 'name' => 'Updated']);
    }

    /** @return iterable<string, array{string, string, mixed, string, string}> */
    public static function invalidAssignments(): iterable
    {
        foreach (['user' => ['roles', 'permisos'], 'role' => ['permisos']] as $resource => $fields) {
            foreach ($fields as $field) {
                foreach ([
                    'null' => [null, $field, "The $field field must be an array."],
                    'scalar' => ['administrador', $field, "The $field field must be an array."],
                    'associative' => [['id' => 1], $field, "The $field field must be a list."],
                    'missing id' => [[999999], $field.'.0', "The selected $field.0 is invalid."],
                    'name' => [['administrador'], $field.'.0', "The $field.0 field must be an integer."],
                    'nested' => [[['id' => 1]], $field.'.0', "The $field.0 field must be an integer."],
                    'null element' => [[null], $field.'.0', "The $field.0 field is required."],
                ] as $case => [$value, $error, $message]) {
                    yield "$resource.$field.$case" => [$resource, $field, $value, $error, $message];
                }
            }
        }
    }

    #[DataProvider('invalidAssignments')]
    public function test_invalid_assignments_return_422_without_partial_updates(string $resource, string $field, mixed $value, string $error, string $message): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.'.update']);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $permission = Permission::factory()->create();
        $target->givePermissionTo($permission);
        $before = $target->fresh()->getAttributes();
        $auditCount = DB::table('activity_log')->count();

        $response = $this->putJson('/api/'.$resource.'/'.$target->id, [
            'nombre' => 'Invalid Change', 'correo' => 'changed@example.com', 'password' => 'password123',
            $field => $value,
        ])->assertUnprocessable();

        $this->assertArrayHasKey($error, $response->json('error'));
        $this->assertSame($message, $response->json('error')[$error][0]);
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame([$permission->id], $target->fresh()->permissions->pluck('id')->all());
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function restrictedAssignments(): iterable
    {
        foreach (['user' => ['roles', 'permisos'], 'role' => ['permisos']] as $resource => $fields) {
            foreach ($fields as $field) {
                foreach (['web', 'deleted', 'duplicate'] as $state) {
                    yield "$resource.$field.$state" => [$resource, $field, $state];
                }
            }
        }
    }

    #[DataProvider('restrictedAssignments')]
    public function test_unassignable_ids_return_422_without_creating_a_record(string $resource, string $field, string $state): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.'.create']);
        $model = $field === 'roles' ? Role::class : Permission::class;
        $assignment = $model::factory()->create(['guard_name' => $state === 'web' ? 'web' : 'api']);
        if ($state === 'deleted') {
            $assignment->delete();
        }
        $ids = $state === 'duplicate' ? [$assignment->id, (string) $assignment->id] : [$assignment->id];
        $auditCount = DB::table('activity_log')->count();

        $response = $this->postJson('/api/'.$resource, [
            'nombre' => 'Invalid Creation', 'correo' => 'invalid@example.com', 'password' => 'password123',
            $field => $ids,
        ])->assertUnprocessable();

        $this->assertArrayHasKey($field.'.0', $response->json('error'));
        $this->assertSame(
            $state === 'duplicate' ? "The $field.0 field has a duplicate value." : "The selected $field.0 is invalid.",
            $response->json('error')[$field.'.0'][0],
        );
        $this->assertDatabaseMissing($resource === 'user' ? 'users' : 'roles', ['name' => 'Invalid Creation']);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    #[TestWith(['user', false])]
    #[TestWith(['user', true])]
    #[TestWith(['role', false])]
    #[TestWith(['role', true])]
    public function test_sync_failure_rolls_back_attributes_assignments_and_audit(string $resource, bool $update): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.($update ? '.update' : '.create')]);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $originalPermission = Permission::factory()->create();
        $newPermission = Permission::factory()->create();
        $actor->givePermissionTo([$originalPermission, $newPermission]);
        $target->givePermissionTo($originalPermission);
        $originalRole = Role::factory()->create();
        if ($resource === 'user') {
            $target->assignRole($originalRole);
        }
        $before = $target->fresh()->getAttributes();
        $auditCount = DB::table('activity_log')->count();
        $pivot = $resource === 'user' ? 'model_has_permissions' : 'role_has_permissions';
        DB::listen(function (QueryExecuted $query) use ($pivot): void {
            if (str_starts_with($query->sql, 'insert into "'.$pivot.'"')) {
                throw new RuntimeException('Controlled synchronization failure');
            }
        });

        $this->json($update ? 'PUT' : 'POST', '/api/'.$resource.($update ? '/'.$target->id : ''), [
            'nombre' => 'Rolled Back', 'correo' => 'rollback@example.com', 'password' => 'password123',
            'roles' => [], 'permisos' => [$newPermission->id],
        ])->assertInternalServerError();

        $this->assertDatabaseMissing($target->getTable(), ['name' => 'Rolled Back']);
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame([$originalPermission->id], $target->fresh()->permissions->pluck('id')->all());
        if ($resource === 'user') {
            $this->assertSame([$originalRole->id], $target->fresh()->roles->pluck('id')->all());
        }
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_model_updates_ignore_the_current_request_and_preserve_assignments(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);
        request()->merge(['roles' => [], 'permissions' => []]);

        $user->update(['name' => 'Console Update']);

        $this->assertSame([$role->id], $user->fresh()->roles->pluck('id')->all());
        $this->assertSame([$permission->id], $user->fresh()->permissions->pluck('id')->all());
    }

    #[TestWith(['user'])]
    #[TestWith(['role'])]
    public function test_authorized_updates_replace_assignments(string $resource): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, [$resource.'.update']);
        $target = $resource === 'user' ? User::factory()->create() : Role::factory()->create();
        $oldPermission = Permission::factory()->create();
        $newPermission = Permission::factory()->create(['name' => 'drug.view']);
        $actor->givePermissionTo([$oldPermission, $newPermission]);
        $target->givePermissionTo($oldPermission);
        $oldRole = Role::factory()->create();
        $newRole = Role::factory()->create();
        if ($resource === 'user') {
            $target->assignRole($oldRole);
        }

        $response = $this->putJson('/api/'.$resource.'/'.$target->id, [
            'nombre' => 'Reassigned', 'correo' => 'reassigned@example.com', 'password' => 'password123',
            'roles' => [$newRole->id], 'permisos' => [$newPermission->id],
        ])->assertOk()->assertJsonPath('data.permisos', [$newPermission->id]);

        $this->assertSame([$newPermission->id], $target->fresh()->permissions->pluck('id')->all());
        $this->assertTrue($target->fresh()->hasPermissionTo($newPermission));
        $this->assertFalse($target->fresh()->hasPermissionTo($oldPermission));
        if ($resource === 'user') {
            $response->assertJsonPath('data.roles', [$newRole->id]);
            $this->assertSame([$newRole->id], $target->fresh()->roles->pluck('id')->all());
        }
    }

    public function test_api_administration_returns_403_for_a_role_from_another_guard(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'api');
        $this->grantPermissions($actor, ['role.update']);
        $role = Role::factory()->create(['guard_name' => 'web']);

        $this->putJson('/api/role/'.$role->id, ['nombre' => 'Changed', 'permisos' => []])->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $role->name, 'guard_name' => 'web']);
    }
}
