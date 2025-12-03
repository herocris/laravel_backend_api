<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteRoleTest extends TestCase
{
    private User $admin;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->role = Role::factory()->create(['name' => 'TempRole']);
    }

    #[Test]
    public function destroy_role(): void
    {
        $response = $this->deleteJson(route('role.destroy', ['role' => $this->role->id]));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->role->id,
            'nombre' => $this->role->name,
            'permisos' => [],
        ]);
        $this->assertSoftDeleted('roles', ['id' => $this->role->id]);
    }

    #[Test]
    public function delete_deleted_role(): void
    {
        $response = $this->deleteJson(route('role.destroy', ['role' => $this->role->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('role.destroy', ['role' => $this->role->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo role",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401(): void
    {
        Auth::logout();

        $response = $this->deleteJson(route('role.destroy', ['role' => $this->role->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
