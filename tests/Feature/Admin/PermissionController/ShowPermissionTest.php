<?php

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowPermissionTest extends TestCase
{
    private User $user;
    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->permission = Permission::factory()->create(['name' => 'test_permission']);
    }

    #[Test]
    public function show_returns_single_permission()
    {
        $response = $this->getJson(route('permission.show', ['permission' => $this->permission->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->permission->id,
            'nombre' => 'test_permission',
        ]);
    }

    #[Test]
    public function show_404_to_inactive_permission(): void
    {
        $response = $this->deleteJson(route('permission.destroy', ['permission' => $this->permission->id]));
        $response->assertOk();
        $response = $this->getJson(route('permission.show', ['permission' => $this->permission->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo permission",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('permission.show', ['permission' => $this->permission->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
