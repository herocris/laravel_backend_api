<?php

namespace Tests\Feature;

use App\Http\Middleware\SerializeAdministrativeMutations;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\AdministrativeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdministrativeTransactionTest extends TestCase
{
    public function test_returns_403_after_revocation_even_with_cached_actor_and_permission_registry(): void
    {
        $actor = User::factory()->create();
        $permission = Permission::findOrCreate('role.create', 'api');
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);
        $actor->assignRole($role);
        $this->assertTrue($actor->can('role.create'));
        $actor->load('roles.permissions', 'permissions');
        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
        $auditCount = DB::table('activity_log')->count();

        $this->actingAs($actor, 'api')->postJson('/api/role', ['nombre' => 'Forbidden role'])
            ->assertForbidden()->assertJsonPath('code', 403);

        $this->assertDatabaseMissing('roles', ['name' => 'Forbidden role']);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_rolls_back_writes_and_audit_when_pipeline_returns_an_error_response(): void
    {
        $actor = User::factory()->create();
        Route::post('/api/administrative-rollback', function () {
            Role::factory()->create(['name' => 'Rolled back']);

            return response()->json(['error' => 'rechazada', 'code' => 422], 422);
        })->middleware(['auth:api', SerializeAdministrativeMutations::class]);
        $auditCount = DB::table('activity_log')->count();

        $this->actingAs($actor, 'api')->postJson('/api/administrative-rollback')
            ->assertUnprocessable()->assertExactJson(['error' => 'rechazada', 'code' => 422]);

        $this->assertDatabaseMissing('roles', ['name' => 'Rolled back']);
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->grantPermissions($actor, ['role.create']);
        $this->postJson('/api/role', ['nombre' => 'After rollback'])->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'After rollback']);
    }

    public function test_restores_permission_cache_configuration_after_nested_operations_and_failure(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $key = $registrar->cacheKey;
        $expiration = $registrar->cacheExpirationTime;
        $transaction = app(AdministrativeTransaction::class);

        try {
            $transaction->run(function () use ($transaction): void {
                $transaction->run(function (): void {
                    Role::factory()->create(['name' => 'Nested rollback']);
                    throw new RuntimeException('rollback');
                });
            });
            $this->fail('Expected rollback.');
        } catch (RuntimeException $exception) {
            $this->assertSame('rollback', $exception->getMessage());
        }

        $this->assertSame($key, $registrar->cacheKey);
        $this->assertSame($expiration, $registrar->cacheExpirationTime);
        $this->assertDatabaseMissing('roles', ['name' => 'Nested rollback']);
        $transaction->run(fn () => Role::factory()->create(['name' => 'After exception']));
        $this->assertDatabaseHas('roles', ['name' => 'After exception']);
    }

    public function test_missing_lock_fails_closed_without_executing_the_mutation(): void
    {
        DB::table('authorization_locks')->where('name', 'api')->delete();

        try {
            app(AdministrativeTransaction::class)->run(fn () => Role::factory()->create(['name' => 'Unsafe']));
            $this->fail('Expected missing lock to fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Falta el bloqueo', $exception->getMessage());
        }

        $this->assertDatabaseMissing('roles', ['name' => 'Unsafe']);
    }
}
