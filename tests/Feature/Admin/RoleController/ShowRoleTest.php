<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowRoleTest extends TestCase
{
    private User $admin;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        
        $this->role = Role::factory()->create(['name' => 'Editor']);
        $perm = Permission::factory()->create(['name' => 'edit.posts']);
        $this->role->givePermissionTo($perm);
    }

    #[Test]
    public function show_returns_role_with_permissions(): void
    {
        $response = $this->getJson(route('role.show', ['role' => $this->role->id]));
        
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->role->id,
            'nombre' => 'Editor',
            'permisos' => [$this->role->permissions->first()->id],
        ]);
    }

    #[Test]
    public function show_404_to_inactive_role(): void
    {
        $response = $this->deleteJson(route('role.destroy', ['role' => $this->role->id]));
        $response->assertOk();
        $response = $this->getJson(route('role.show', ['role' => $this->role->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo role",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('role.show', ['role' => $this->role->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
