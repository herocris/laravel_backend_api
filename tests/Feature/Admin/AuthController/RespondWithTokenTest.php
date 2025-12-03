<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RespondWithTokenTest extends TestCase
{
    private User $user;
    private User $admin;
    private array $user_data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => $this->user_data['name'],
            'email' => $this->user_data['email'],
            'password' => bcrypt($this->user_data['password']),
        ]);
        $this->admin = $this->superAdminLogin();
    }

    #[Test]
    public function respond_with_token_includes_user_roles(): void
    {
        $this->actingAs($this->admin, 'api');
        $role = Role::factory()->create(['name' => 'admin', 'guard_name' => 'api']);
        $this->user->assignRole($role);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'roles' => ['admin'],
        ]);
    }

    #[Test]
    public function respond_with_token_includes_multiple_roles(): void
    {
        $this->actingAs($this->admin, 'api');
        $adminRole = Role::factory()->create(['name' => 'admin', 'guard_name' => 'api']);
        $editorRole = Role::factory()->create(['name' => 'editor', 'guard_name' => 'api']);

        $this->user->assignRole([$adminRole, $editorRole]);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'roles' => ['admin', 'editor'],
        ]);
    }

    #[Test]
    public function respond_with_token_includes_empty_roles_when_user_has_no_roles(): void
    {
        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'roles' => [],
        ]);
    }

    #[Test]
    public function respond_with_token_includes_user_permissions(): void
    {
        $this->actingAs($this->admin, 'api');
        $permission = Permission::create(['name' => 'edit-posts', 'guard_name' => 'api']);
        $this->user->givePermissionTo($permission);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'permissions' => ['edit-posts'],
        ]);
    }

    #[Test]
    public function respond_with_token_includes_permissions_from_roles(): void
    {
        $this->actingAs($this->admin, 'api');
        $permission = Permission::create(['name' => 'delete-posts', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'moderator', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $this->user->assignRole($role);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'permissions' => ['delete-posts'],
        ]);
    }

    #[Test]
    public function respond_with_token_includes_all_permissions_from_roles_and_direct(): void
    {
        $this->actingAs($this->admin, 'api');
        $directPermission = Permission::create(['name' => 'view-posts', 'guard_name' => 'api']);
        $rolePermission = Permission::create(['name' => 'create-posts', 'guard_name' => 'api']);

        $role = Role::create(['name' => 'writer', 'guard_name' => 'api']);
        $role->givePermissionTo($rolePermission);

        $this->user->assignRole($role);
        $this->user->givePermissionTo($directPermission);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $permissions = $response->json('permissions');
        $this->assertContains('view-posts', $permissions);
        $this->assertContains('create-posts', $permissions);
    }

    #[Test]
    public function respond_with_token_includes_empty_permissions_when_user_has_no_permissions(): void
    {
        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertJson([
            'permissions' => [],
        ]);
    }

    #[Test]
    public function respond_with_token_permissions_are_returned_as_array_of_strings(): void
    {
        $this->actingAs($this->admin, 'api');
        Permission::create(['name' => 'permission-one', 'guard_name' => 'api']);
        Permission::create(['name' => 'permission-two', 'guard_name' => 'api']);

        $this->user->givePermissionTo(['permission-one', 'permission-two']);
        Auth::guard('api')->forgetUser();

        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $permissions = $response->json('permissions');

        $this->assertIsArray($permissions);
        foreach ($permissions as $permission) {
            $this->assertIsString($permission);
        }
    }
}
