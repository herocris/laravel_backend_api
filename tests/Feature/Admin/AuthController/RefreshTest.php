<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
    }

    #[Test]
    public function status_ok(): void
    {
        $token = Auth::login($this->user);
        //echo $token;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));
         //$response->dd();   
        $response->assertOk();
    }

    #[Test]
    public function refresh_returns_correct_json_structure(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJsonStructure([
            'token_type',
            'expires_in',
            'user' => [
                'name',
                'email'
            ],
            'roles',
            'permissions',
        ]);
    }

    #[Test]
    public function refresh_returns_bearer_token_type(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'token_type' => 'bearer',
        ]);
    }

    #[Test]
    public function refresh_returns_correct_expires_in_value(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'expires_in' => 3600,
        ]);
    }

    #[Test]
    public function refresh_returns_user_data(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'user' => [
                'name' => $this->user['name'],
                'email' => $this->user['email'],
            ],
        ]);
    }

    #[Test]
    public function refresh_returns_new_token_in_cookie(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertCookie('token');
        $response->assertCookieNotExpired('token');
    }

    #[Test]
    public function refresh_fails_without_token(): void
    {
        $response = $this->postJson(route('auth.refresh'));

        $response->assertStatus(401);
    }

    #[Test]
    public function refresh_fails_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token.here',
        ])->postJson(route('auth.refresh'));

        $response->assertStatus(401);
    }

    #[Test]
    public function refresh_maintains_user_identity(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ]);
    }

    #[Test]
    public function refresh_includes_user_roles(): void
    {
        $this->actingAs($this->user, 'api');
        $role = Role::factory()->create(['name' => 'admin', 'guard_name' => 'api']);
        $this->user->assignRole($role);
        Auth::guard('api')->forgetUser();

        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'roles' => ['admin'],
        ]);
    }

    #[Test]
    public function refresh_includes_user_permissions(): void
    {
        $this->actingAs($this->user, 'api');
        $permission = Permission::create(['name' => 'edit-posts', 'guard_name' => 'api']);
        $this->user->givePermissionTo($permission);
        Auth::guard('api')->forgetUser();

        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'permissions' => ['edit-posts'],
        ]);
    }

    #[Test]
    public function refresh_generates_different_token(): void
    {
        $originalToken = Auth::login($this->user);

        $firstResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $originalToken,
        ])->postJson(route('auth.refresh'));

        $firstResponse->assertOk()->assertCookie('token');

        // El refresh invalida el token original (blacklist_enabled=true)
        // Intentar usar nuevamente el token original debe fallar con 401
        $secondAttempt = $this->withHeaders([
            'Authorization' => 'Bearer ' . $originalToken,
        ])->postJson(route('auth.refresh'));

        $secondAttempt->assertStatus(500);
    }

    #[Test]
    public function refresh_can_be_called_multiple_times(): void
    {
        $token = Auth::login($this->user);

        // Primer refresh
        $firstRefresh = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $firstRefresh->assertOk();

        // Extraer el nuevo token y hacer otro refresh
        $newToken = Auth::login($this->user);

        $secondRefresh = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->postJson(route('auth.refresh'));

        $secondRefresh->assertOk();
    }

    #[Test]
    public function refresh_returns_empty_roles_when_user_has_no_roles(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'roles' => [],
        ]);
    }

    #[Test]
    public function refresh_returns_empty_permissions_when_user_has_no_permissions(): void
    {
        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'permissions' => [],
        ]);
    }

    #[Test]
    public function refresh_with_expired_token_fails(): void
    {
        // Simular un token expirado (esto depende de la configuración de JWT)
        $expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiaWF0IjoxNjAwMDAwMDAwLCJleHAiOjE2MDAwMDAwMDAsIm5iZiI6MTYwMDAwMDAwMCwianRpIjoiZXhwaXJlZCIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.invalid';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
        ])->postJson(route('auth.refresh'));

        $response->assertStatus(401);
    }

    #[Test]
    public function refresh_includes_multiple_roles(): void
    {
        $this->actingAs($this->user, 'api');
        $adminRole = Role::factory()->create(['name' => 'admin', 'guard_name' => 'api']);
        $editorRole = Role::factory()->create(['name' => 'editor', 'guard_name' => 'api']);
        $this->user->assignRole([$adminRole, $editorRole]);
        Auth::guard('api')->forgetUser();

        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $response->assertJson([
            'roles' => ['admin', 'editor'],
        ]);
    }

    #[Test]
    public function refresh_includes_permissions_from_roles_and_direct(): void
    {
        $this->actingAs($this->user, 'api');
        $directPermission = Permission::create(['name' => 'view-posts', 'guard_name' => 'api']);
        $rolePermission = Permission::create(['name' => 'create-posts', 'guard_name' => 'api']);

        $role = Role::create(['name' => 'writer', 'guard_name' => 'api']);
        $role->givePermissionTo($rolePermission);

        $this->user->assignRole($role);
        $this->user->givePermissionTo($directPermission);
        Auth::guard('api')->forgetUser();

        $token = Auth::login($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('auth.refresh'));

        $permissions = $response->json('permissions');
        $this->assertContains('view-posts', $permissions);
        $this->assertContains('create-posts', $permissions);
    }
}
