<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Actions\Role\SaveRoleAction;
use App\Actions\User\SaveUserAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MeTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUnprivilegedUser();
    }

    #[Test]
    public function me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson(route('auth.me'));
        $response->assertUnauthorized()->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }

    #[Test]
    public function me_returns_status_ok_when_authenticated(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson(route('auth.me'));

        $response->assertOk();
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function me_returns_correct_user_data(bool $cookie): void
    {
        $token = Auth::login($this->user);
        $response = $this->getWithToken($token, $cookie);

        $response->assertOk()->assertExactJson([
            'user' => ['name' => $this->user->name, 'email' => $this->user->email],
            'roles' => [],
            'permissions' => [],
        ]);
        $this->assertSame([], json_decode($response->getContent())->roles);
        $this->assertSame([], json_decode($response->getContent())->permissions);
    }

    #[Test]
    public function me_hides_sensitive_fields(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson(route('auth.me'));

        $data = $response->json('user');
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    #[Test]
    public function me_returns_json_content_type(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson(route('auth.me'));

        $response->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function me_returns_roles_and_unique_direct_and_inherited_permission_names(): void
    {
        $direct = Permission::factory()->create(['name' => 'drug.create', 'guard_name' => 'api']);
        $shared = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'api']);
        $inherited = Permission::factory()->create(['name' => 'map.view', 'guard_name' => 'api']);
        $role = Role::factory()->create(['name' => 'analista', 'guard_name' => 'api']);
        $otherRole = Role::factory()->create(['name' => 'operador', 'guard_name' => 'api']);
        $role->syncPermissions([$shared, $inherited]);
        $otherRole->givePermissionTo($shared);
        $this->user->assignRole($role, $otherRole);
        $this->user->givePermissionTo($direct, $shared);
        $token = Auth::login($this->user);

        $response = $this->getWithToken($token)->assertOk()
            ->assertJsonPath('roles', ['analista', 'operador']);

        $permissions = $response->json('permissions');
        sort($permissions);
        $this->assertSame(['drug.create', 'drug.view', 'map.view'], $permissions);
    }

    #[Test]
    public function me_does_not_infer_permissions_from_an_administrator_role_name(): void
    {
        $role = Role::factory()->create(['name' => 'administrador', 'guard_name' => 'api']);
        $this->user->assignRole($role);
        $token = Auth::login($this->user);

        $this->getWithToken($token)->assertOk()
            ->assertJsonPath('roles', ['administrador'])->assertJsonPath('permissions', []);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function me_reflects_assignment_and_revocation_with_the_same_jwt(bool $cookie): void
    {
        $direct = Permission::factory()->create(['name' => 'drug.create', 'guard_name' => 'api']);
        $shared = Permission::factory()->create(['name' => 'drug.view', 'guard_name' => 'api']);
        $role = Role::factory()->create(['name' => 'operador', 'guard_name' => 'api']);
        $token = Auth::login($this->user);
        app(PermissionRegistrar::class)->getPermissions();
        $this->getWithToken($token, $cookie)->assertOk()
            ->assertJsonPath('roles', [])->assertJsonPath('permissions', []);

        $role->givePermissionTo($shared);
        app(SaveUserAction::class)->execute($this->user->fresh(), [
            'roles' => [$role->id], 'permissions' => [$direct->id, $shared->id],
        ]);
        $response = $this->getWithToken($token, $cookie)->assertOk()
            ->assertJsonPath('roles', ['operador'])->assertJsonCount(2, 'permissions');
        $this->assertContains('drug.create', $response->json('permissions'));
        $this->assertContains('drug.view', $response->json('permissions'));
        app(PermissionRegistrar::class)->getPermissions();

        $this->user->fresh()->revokePermissionTo($shared);
        $this->getWithToken($token, $cookie)->assertOk()->assertJsonCount(2, 'permissions');

        $this->user->fresh()->revokePermissionTo($direct);
        $this->getWithToken($token, $cookie)->assertOk()->assertJsonPath('permissions', ['drug.view']);

        app(SaveRoleAction::class)->execute($role->fresh(), ['permissions' => []]);
        $this->getWithToken($token, $cookie)->assertOk()
            ->assertJsonPath('roles', ['operador'])->assertJsonPath('permissions', []);

        $role->fresh()->givePermissionTo($shared);
        $this->getWithToken($token, $cookie)->assertOk()->assertJsonPath('permissions', ['drug.view']);

        app(SaveUserAction::class)->execute($this->user->fresh(), ['roles' => []]);
        $this->getWithToken($token, $cookie)->assertOk()
            ->assertJsonPath('roles', [])->assertJsonPath('permissions', []);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function me_does_not_emit_a_jwt_cookie_or_invalidate_or_extend_the_token(bool $cookie): void
    {
        $this->freezeTime();
        config(['jwt.ttl' => 2]);
        $this->resetJwtAuthentication();
        $token = Auth::login($this->user);

        $this->getWithToken($token, $cookie)->assertOk()->assertCookieMissing('token');
        $this->travel(60)->seconds();
        $this->getWithToken($token, $cookie)->assertOk()->assertCookieMissing('token');
        $this->travel(61)->seconds();
        $this->getWithToken($token, $cookie)->assertUnauthorized()->assertExactJson([
            'code' => 401, 'error' => 'No autenticado',
        ]);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function me_returns_401_for_an_invalid_token(bool $cookie): void
    {
        $this->getWithToken('invalid.token.here', $cookie)->assertUnauthorized()->assertExactJson([
            'code' => 401, 'error' => 'No autenticado',
        ]);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function me_returns_401_for_a_revoked_token(bool $cookie): void
    {
        $token = Auth::login($this->user);
        Auth::setToken($token)->invalidate();

        $this->getWithToken($token, $cookie)->assertUnauthorized()->assertExactJson([
            'code' => 401, 'error' => 'No autenticado',
        ]);
    }

    private function getWithToken(string $token, bool $cookie = false): TestResponse
    {
        $this->resetJwtAuthentication();
        $this->unencryptedCookies = $cookie ? ['token' => $token] : [];
        $this->withCredentials();

        return $this->getJson(route('auth.me'), $cookie ? [] : ['Authorization' => 'Bearer '.$token]);
    }
}
