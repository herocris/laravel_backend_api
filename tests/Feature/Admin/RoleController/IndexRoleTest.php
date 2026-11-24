<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexRoleTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->grantPermissions($this->admin, ['role.view']);
    }

    #[Test]
    public function index_returns_all_roles(): void
    {
        Role::factory()->count(10)->create();
        $response = $this->getJson(route('role.index'));
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
    public function empty_role_collection(): void
    {
        $response = $this->getJson(route('role.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('role.index'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
