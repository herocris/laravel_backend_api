<?php

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexPermissionTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
    }

    #[Test]
    public function index_returns_all_permissions()
    {
        Permission::factory()->count(10)->create();

        $response = $this->getJson(route('permission.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'nombre',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links' => [
                '*' => [
                    'url',
                    'label',
                    'active',
                ],
            ],
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        $response->assertJsonCount(10, 'data');
    }
    #[Test]
    public function empty_permission_collection(): void
    {
        $response = $this->getJson(route('permission.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('permission.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
