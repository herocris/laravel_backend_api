<?php

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePermissionTest extends TestCase
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
    }

    #[Test]
    public function delete_permission()
    {
        $response = $this->deleteJson(route('permission.destroy', $this->permission->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->permission->id,
            'nombre' => $this->permission->name,
        ]);
        $this->assertSoftDeleted('permissions', ['id' => $this->permission->id]);
    }

    #[Test]
    public function delete_deleted_permission(): void
    {
        $response = $this->deleteJson(route('permission.destroy', ['permission' => $this->permission->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('permission.destroy', ['permission' => $this->permission->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo permission",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('permission.destroy', ['permission' => $this->permission->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
