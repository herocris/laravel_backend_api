<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuleAuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, array<string, string>, int}>
     */
    public static function protectedEndpoints(): array
    {
        $cases = [];

        foreach ([
            'user', 'role', 'permission', 'drug', 'drugPresentation', 'weapon',
            'ammunition', 'confiscation', 'drugConfiscation', 'weaponConfiscation',
            'ammunitionConfiscation',
        ] as $resource) {
            $cases[$resource.'.index'] = ['GET', '/api/'.$resource, $resource.'.view', [], 200];
            $cases[$resource.'.show'] = ['GET', '/api/'.$resource.'/999999', $resource.'.view', [], 404];
            $cases[$resource.'.deleted'] = ['GET', '/api/'.$resource.'/deleted', $resource.'.view', [], 200];
            $cases[$resource.'.store'] = ['POST', '/api/'.$resource, $resource.'.create', [], 422];
            $cases[$resource.'.update.put'] = ['PUT', '/api/'.$resource.'/999999', $resource.'.update', [], 404];
            $cases[$resource.'.update.patch'] = ['PATCH', '/api/'.$resource.'/999999', $resource.'.update', [], 404];
            $cases[$resource.'.restore'] = ['POST', '/api/'.$resource.'/restore/999999', $resource.'.update', [], 404];
            $cases[$resource.'.destroy'] = ['DELETE', '/api/'.$resource.'/999999', $resource.'.delete', [], 404];
        }

        foreach (['drug' => 'drugs', 'weapon' => 'weapons', 'ammunition' => 'ammunitions'] as $resource => $filter) {
            $cases[$resource.'Confiscation.photo'] = ['GET', '/api/'.$resource.'Confiscation/999999/photo', $resource.'Confiscation.view', [], 404];
            $cases[$resource.'.byConfiscation'] = ['GET', '/api/'.$resource.'Confiscation/999999/confiscation', $resource.'Confiscation.view', [], 200];
            $cases[$resource.'.graph'] = ['GET', '/api/'.$resource.'GraphIndex', 'graph.view', [
                'period' => 'month',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                $filter => '[]',
                'typeGraph' => 'bar',
            ], 200];
        }

        $cases['map'] = ['GET', '/api/confiscation/map', 'map.view', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ], 200];
        $cases['activity'] = ['GET', '/api/activity', 'activity.view', [], 200];

        foreach ($cases as $name => $case) {
            if ($case[0] === 'GET') {
                $case[0] = 'HEAD';
                $cases[$name.'.head'] = $case;
            }
        }

        return $cases;
    }

    public function test_the_http_matrix_covers_every_module_route_method_and_permission(): void
    {
        $expected = [];
        foreach (self::protectedEndpoints() as $name => [$method, $uri, $permission]) {
            $name = preg_replace('/\.head$/', '', $name);
            $action = explode('.', $name)[1] ?? $name;
            $action = match ($action) {
                'deleted' => 'indexDeleted',
                'byConfiscation' => 'indexByConfiscation',
                'graph' => 'graphIndex',
                'map' => 'mapConfiscations',
                'activity' => '__invoke',
                default => $action,
            };
            $expected[$method.' '.$uri] = ['permission' => $permission, 'action' => $action];
        }

        $actual = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/') || str_starts_with(ltrim($route->getActionName(), '\\'), 'L5Swagger\\') || in_array($route->getName(), [
                'auth.login', 'auth.register', 'auth.me', 'auth.logout', 'auth.refresh', 'auth.csrf-cookie',
            ], true)) {
                continue;
            }

            $this->assertContains(Authenticate::class, app('router')->gatherRouteMiddleware($route), $route->uri().' '.$route->getActionName());
            $permissions = array_values(array_filter(
                $route->gatherMiddleware(),
                fn (string $middleware): bool => str_starts_with($middleware, 'can:'),
            ));
            $this->assertCount(1, $permissions, $route->uri());
            $this->assertContains(Authorize::class.':'.substr($permissions[0], 4), app('router')->gatherRouteMiddleware($route));

            $uri = '/'.preg_replace('/\{[^}]+\}/', '999999', $route->uri());
            foreach ($route->methods() as $method) {
                $actual[$method.' '.$uri] = [
                    'permission' => substr($permissions[0], 4),
                    'action' => str_contains($route->getActionName(), '@') ? $route->getActionMethod() : '__invoke',
                ];
            }
        }

        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param  array<string, string>  $payload
     */
    #[DataProvider('protectedEndpoints')]
    public function test_guests_receive_401(
        string $method,
        string $uri,
        string $permission,
        array $payload,
        int $authorizedStatus,
    ): void {
        $response = $this->json($method, $uri, $payload)->assertUnauthorized();

        if ($method === 'HEAD') {
            $response->assertContent('');
        } else {
            $response->assertExactJson(['error' => 'No autenticado', 'code' => 401]);
        }
    }

    /**
     * @param  array<string, string>  $payload
     */
    #[DataProvider('protectedEndpoints')]
    public function test_users_without_the_permission_receive_403(
        string $method,
        string $uri,
        string $permission,
        array $payload,
        int $authorizedStatus,
    ): void {
        $user = $this->createUnprivilegedUser();
        $this->actingAs($user, 'api');

        $response = $this->json($method, $uri, $payload)->assertForbidden();

        if ($method === 'HEAD') {
            $response->assertContent('');
        } else {
            $response->assertJsonPath('code', 403);
        }
    }

    /**
     * @param  array<string, string>  $payload
     */
    #[DataProvider('protectedEndpoints')]
    public function test_only_the_required_permission_allows_access_to_the_action(
        string $method,
        string $uri,
        string $permission,
        array $payload,
        int $authorizedStatus,
    ): void {
        $user = $this->createUnprivilegedUser();
        $this->actingAs($user, 'api');
        $this->grantPermissions($user, [$permission]);

        $this->json($method, $uri, $payload)->assertStatus($authorizedStatus);
    }

    public function test_an_unrelated_permission_does_not_authorize_writes(): void
    {
        $user = $this->createUnprivilegedUser();
        $this->actingAs($user, 'api');
        $this->grantPermissions($user, ['drug.view']);

        $this->postJson('/api/drug')->assertForbidden();
        $this->putJson('/api/drug/999999')->assertForbidden();
        $this->postJson('/api/drug/restore/999999')->assertForbidden();
        $this->deleteJson('/api/drug/999999')->assertForbidden();
    }

    public function test_permissions_inherited_from_a_role_authorize_access(): void
    {
        $user = $this->createUnprivilegedUser();
        $this->actingAs($user, 'api');
        $permission = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'api']);
        $role = Role::factory()->create(['guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->getJson('/api/drug')->assertOk();
    }

    public function test_an_administrator_role_without_permissions_is_denied(): void
    {
        $user = $this->createUnprivilegedUser();
        $this->actingAs($user, 'api');
        $user->assignRole(Role::factory()->create(['name' => 'administrador', 'guard_name' => 'api']));

        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_a_permission_from_another_guard_does_not_authorize_api_access(): void
    {
        $user = $this->createUnprivilegedUser();
        $user->givePermissionTo(Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'web']));
        $this->actingAs($user, 'api');

        $this->getJson('/api/drug')->assertForbidden()->assertJsonPath('code', 403);
    }

    public function test_authenticating_only_with_the_web_guard_does_not_authorize_api_access(): void
    {
        $user = $this->createUnprivilegedUser();
        $this->grantPermissions($user, ['drug.view']);
        $this->actingAs($user, 'web');
        app('auth')->shouldUse('api');

        $this->getJson('/api/drug')->assertUnauthorized()->assertJsonPath('code', 401);
    }

    public function test_the_legacy_user_helper_does_not_authenticate_or_grant_privileges(): void
    {
        $user = $this->superAdminLogin();

        $this->assertGuest('api');
        $this->assertCount(0, $user->roles);
        $this->assertCount(0, $user->getAllPermissions());
    }
}
