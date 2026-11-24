<?php

namespace Tests\Feature;

use App\Actions\Role\SaveRoleAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\AdministrativeTransaction;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\StatisticsTestCase;
use Throwable;

class AdministrativeConcurrencyTest extends StatisticsTestCase
{
    /** @var list<int> */
    private array $userIds = [];

    /** @var list<int> */
    private array $roleIds = [];

    /** @var list<int> */
    private array $permissionIds = [];

    public function refreshDatabase(): void
    {
        $this->beforeRefreshingDatabase();
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('La concurrencia requiere phpunit.mysql.xml y conexiones MySQL independientes.');
        }
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La concurrencia requiere pcntl de Sail.');
        }

        $this->migrateDatabases();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->app !== null && ($this->userIds !== [] || $this->roleIds !== [] || $this->permissionIds !== [])) {
                DB::table('activity_log')->where(function (Builder $query): void {
                    $query->where('causer_type', User::class)->whereIn('causer_id', $this->userIds);
                })->orWhere(function (Builder $query): void {
                    $query->where('subject_type', User::class)->whereIn('subject_id', $this->userIds);
                })->orWhere(function (Builder $query): void {
                    $query->where('subject_type', Role::class)->whereIn('subject_id', $this->roleIds);
                })->orWhere(function (Builder $query): void {
                    $query->where('subject_type', Permission::class)->whereIn('subject_id', $this->permissionIds);
                })->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $this->userIds)->delete();
                DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $this->userIds)->delete();
                DB::table('users')->whereIn('id', $this->userIds)->delete();
                DB::table('roles')->whereIn('id', $this->roleIds)->delete();
                DB::table('permissions')->whereIn('id', $this->permissionIds)->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function administrativeMutations(): iterable
    {
        foreach (['user', 'role', 'permission'] as $resource) {
            foreach (['store', 'update', 'destroy', 'restore'] as $operation) {
                yield $resource.'.'.$operation => [$resource, $operation];
            }
        }
    }

    #[DataProvider('administrativeMutations')]
    public function test_returns_403_if_operation_permission_is_revoked_while_request_waits(string $resource, string $operation): void
    {
        $actor = $this->user();
        $ability = $this->permission($resource.'.'.match ($operation) {
            'store' => 'create', 'destroy' => 'delete', default => 'update',
        });
        $actorRole = $this->role();
        $actorRole->givePermissionTo($ability);
        $actor->assignRole($actorRole);
        $target = match ($resource) {
            'user' => $this->user(), 'role' => $this->role(), default => $this->permission('concurrency.'.Str::uuid()),
        };
        if ($operation === 'restore') {
            $target->delete();
        }
        $before = $target->fresh()->getAttributes();
        $name = 'concurrent-'.Str::uuid();
        $payload = ['nombre' => $name, 'correo' => $actor->email, 'password' => 'test-password'];
        [$method, $uri] = match ($operation) {
            'store' => ['POST', '/api/'.$resource],
            'update' => ['PATCH', '/api/'.$resource.'/'.$target->id],
            'destroy' => ['DELETE', '/api/'.$resource.'/'.$target->id],
            'restore' => ['POST', '/api/'.$resource.'/restore/'.$target->id],
        };

        $this->requestWhileLocked($actor, $method, $uri, $payload,
            fn () => app(SaveRoleAction::class)->execute($actorRole, ['permissions' => []]))
            ->assertForbidden()->assertJsonPath('code', 403);

        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertDatabaseMissing($target->getTable(), ['name' => $name]);
        $this->assertDatabaseMissing('activity_log', ['causer_type' => User::class, 'causer_id' => $actor->id]);
    }

    public function test_returns_403_if_role_gains_unowned_permissions_before_assignment(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('user.update'));
        $target = $this->user();
        $role = $this->role();
        $unowned = $this->permission('drug.delete');

        $this->requestWhileLocked($actor, 'PATCH', '/api/user/'.$target->id, [...$this->userPayload($target), 'roles' => [$role->id]],
            fn () => app(SaveRoleAction::class)->execute($role, ['permissions' => [$unowned->id]]))
            ->assertForbidden();

        $this->assertSame([], $target->fresh()->roles()->pluck('roles.id')->all());
        $this->assertDatabaseMissing('activity_log', ['causer_type' => User::class, 'causer_id' => $actor->id]);
    }

    public function test_returns_403_if_target_user_gains_unowned_privileges_before_takeover(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('user.update'));
        $target = $this->user();
        $email = $target->email;
        $unowned = $this->permission('drug.delete');

        $this->requestWhileLocked($actor, 'PATCH', '/api/user/'.$target->id, [...$this->userPayload($target), 'correo' => 'takeover@example.test'],
            fn () => $target->givePermissionTo($unowned))->assertForbidden();

        $this->assertSame($email, $target->fresh()->email);
        $this->assertSame([$unowned->id], $target->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_returns_403_if_permission_becomes_assigned_before_rename(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('permission.update'));
        $permission = $this->permission('concurrency.'.Str::uuid());
        $name = $permission->name;
        $target = $this->role();

        $this->requestWhileLocked($actor, 'PATCH', '/api/permission/'.$permission->id, ['nombre' => 'renamed.'.Str::uuid()],
            fn () => $target->givePermissionTo($permission))->assertForbidden();

        $this->assertSame($name, $permission->fresh()->name);
        $this->assertSame([$permission->id], $target->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_returns_403_if_authenticated_actor_is_deleted_while_request_waits(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('user.update'));
        $target = $this->user();
        $before = $target->fresh()->getAttributes();

        $this->requestWhileLocked($actor, 'PATCH', '/api/user/'.$target->id, $this->userPayload($target),
            fn () => $actor->delete())->assertForbidden();

        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSoftDeleted($actor);
    }

    public function test_returns_404_if_role_is_deleted_before_route_binding(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('role.update'));
        $target = $this->role();
        $name = $target->name;

        $this->requestWhileLocked($actor, 'PATCH', '/api/role/'.$target->id, ['nombre' => 'Deleted concurrent role'],
            fn () => $target->delete())->assertNotFound();

        $this->assertSame($name, $target->fresh()->name);
        $this->assertSoftDeleted($target);
    }

    public function test_allows_waiting_request_when_committed_state_still_authorizes_delegation(): void
    {
        $actor = $this->user();
        $actor->givePermissionTo($this->permission('user.update'));
        $target = $this->user();
        $role = $this->role();
        $owned = $this->permission('drug.view');
        $actor->givePermissionTo($owned);

        $this->requestWhileLocked($actor, 'PATCH', '/api/user/'.$target->id, [...$this->userPayload($target), 'roles' => [$role->id]],
            fn () => app(SaveRoleAction::class)->execute($role, ['permissions' => [$owned->id]]))->assertOk();

        $this->assertSame([$role->id], $target->fresh()->roles()->pluck('roles.id')->all());
        $this->assertSame([$owned->id], $target->fresh()->getAllPermissions()->pluck('id')->all());
    }

    /**
     * Ejecuta HTTP en otro proceso, reteniendo el mutex hasta comprobar que espera.
     * Los fixtures están confirmados y cada proceso abre su propia conexión.
     *
     * @param  array<string, mixed>  $payload
     * @param  Closure(): mixed  $mutation
     */
    private function requestWhileLocked(User $actor, string $method, string $uri, array $payload, Closure $mutation): TestResponse
    {
        $actor->load('roles.permissions', 'permissions');
        $this->actingAs($actor, 'api');
        app(PermissionRegistrar::class)->getPermissions();
        DB::disconnect();
        [$parent, $child] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_timeout($parent, 15);
        stream_set_timeout($child, 15);
        $pid = pcntl_fork();
        $this->assertNotSame(-1, $pid);
        if ($pid === 0) {
            fclose($parent);
            try {
                if (fgets($child) !== "go\n") {
                    throw new \RuntimeException('No se recibió la barrera inicial.');
                }
                $signaled = false;
                DB::connection()->beforeExecuting(function (string $sql) use ($child, &$signaled): void {
                    if (! $signaled && str_contains($sql, 'authorization_locks')) {
                        $signaled = true;
                        fwrite($child, "waiting\n");
                    }
                });
                $response = $this->json($method, $uri, $payload);
                fwrite($child, json_encode(['status' => $response->status(), 'body' => $response->json()], JSON_THROW_ON_ERROR)."\n");
            } catch (Throwable $exception) {
                fwrite($child, json_encode(['exception' => $exception->getMessage()], JSON_THROW_ON_ERROR)."\n");
            } finally {
                DB::disconnect();
                fclose($child);
            }
            exit(0);
        }

        fclose($child);
        try {
            app(AdministrativeTransaction::class)->run(function () use ($mutation, $parent): void {
                $mutation();
                fwrite($parent, "go\n");
                $this->assertSame("waiting\n", fgets($parent), 'La petición debe intentar adquirir el mutex.');
                $read = [$parent];
                $write = $except = [];
                $this->assertSame(0, stream_select($read, $write, $except, 0, 150000), 'La petición no puede terminar antes del commit.');
            });
            $result = json_decode((string) fgets($parent), true, flags: JSON_THROW_ON_ERROR);
            $this->assertArrayNotHasKey('exception', $result, $result['exception'] ?? '');
            $this->assertArrayHasKey('status', $result);

            return TestResponse::fromBaseResponse(new JsonResponse($result['body'], $result['status']));
        } finally {
            fclose($parent);
            if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
        }
    }

    /** @return array{nombre: string, correo: string, password: string} */
    private function userPayload(User $user): array
    {
        return ['nombre' => $user->name, 'correo' => $user->email, 'password' => 'test-password'];
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $this->userIds[] = $user->id;

        return $user;
    }

    private function role(): Role
    {
        $role = Role::factory()->create();
        $this->roleIds[] = $role->id;

        return $role;
    }

    private function permission(string $name): Permission
    {
        $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        if ($permission->wasRecentlyCreated) {
            $this->permissionIds[] = $permission->id;
        }

        return $permission;
    }
}
