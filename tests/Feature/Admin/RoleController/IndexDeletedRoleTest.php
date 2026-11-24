<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDeletedRoleTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->grantPermissions($this->admin, ['role.view']);
        $roles = Role::factory()->count(10)->create();
        $roles->each->delete(); // para borrar los usuarios creados
    }

    #[Test]
    public function index_deleted_lists_only_soft_deleted_roles(): void
    {

        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'nombre',
                    'permisos',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);
        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function index_deleted_returns_empty_when_no_deleted_roles(): void
    {
        // Restore all deleted roles
        Role::onlyTrashed()->get()->each->restore();

        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertOk();
        $response->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.from', null)
            ->assertJsonPath('meta.to', null);
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_deleted_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
