<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreRoleTest extends TestCase
{
    private User $admin;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        
        $this->role = Role::factory()->create(['name' => 'DeletedRole']);
        $this->role->delete();
    }

    #[Test]
    public function restore_role_successfully(): void
    {
        //assertSoftDeleted comprueba que el recurso está eliminado con soft delete ()
        //comprueba que el rol está eliminado con soft delete
        $this->assertSoftDeleted('roles', ['id' => $this->role->id]);
        
        $response = $this->postJson(route('role.restore', ['role' => $this->role->id]));
        
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->role->id,
            'nombre' => 'DeletedRole',
            'permisos' => [],
        ]);
        
        $this->assertDatabaseHas('roles', [
            'id' => $this->role->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function not_restore_non_existent_role(): void
    {       
        $response = $this->postJson(route('role.restore', ['role' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo role",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('role.restore', ['role' => $this->role->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
