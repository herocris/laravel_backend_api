<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
            $cases[$resource.'.byConfiscation'] = ['GET', '/api/'.$resource.'Confiscation/999999/confiscation', $resource.'Confiscation.view', [], 200];
            $cases[$resource.'.graph'] = ['GET', '/api/'.$resource.'GraphIndex', 'graph.view', [
                'period' => 'day',
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

        return $cases;
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
        $this->json($method, $uri, $payload)->assertUnauthorized()->assertExactJson([
            'error' => 'No autenticado',
            'code' => 401,
        ]);
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
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $this->json($method, $uri, $payload)->assertForbidden()->assertJsonPath('code', 403);
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
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $this->grantPermissions($user, [$permission]);

        $this->json($method, $uri, $payload)->assertStatus($authorizedStatus);
    }

    public function test_an_unrelated_permission_does_not_authorize_writes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $this->grantPermissions($user, ['drug.view']);

        $this->postJson('/api/drug')->assertForbidden();
        $this->putJson('/api/drug/999999')->assertForbidden();
        $this->postJson('/api/drug/restore/999999')->assertForbidden();
        $this->deleteJson('/api/drug/999999')->assertForbidden();
    }

    public function test_permissions_inherited_from_a_role_authorize_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $permission = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'api']);
        $role = Role::factory()->create(['guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->getJson('/api/drug')->assertOk();
    }

    public function test_an_administrator_role_without_permissions_is_denied(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $user->assignRole(Role::factory()->create(['name' => 'administrador', 'guard_name' => 'api']));

        $this->getJson('/api/user')->assertForbidden();
    }
}
