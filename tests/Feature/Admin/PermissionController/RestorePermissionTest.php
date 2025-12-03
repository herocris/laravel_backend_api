<?php

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestorePermissionTest extends TestCase
{
    private User $user;
    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->permission = Permission::factory()->create([
            'name' => 'permission_test'
        ]);
        $this->permission->delete();
    }

    #[Test]
    public function restore_recovers_permission_successfully()
    {
        $this->assertSoftDeleted('permissions', ['id' => $this->permission->id]);

        $response = $this->postJson(route('permission.restore', ['permission' => $this->permission->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->permission->id,
            'nombre' => $this->permission->name,
        ]);
        
        $this->assertDatabaseHas('permissions', [
            'id' => $this->permission->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_permission(): void
    {       
        $response = $this->postJson(route('permission.restore', ['permission' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo permission",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('permission.restore', ['permission' => $this->permission->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
