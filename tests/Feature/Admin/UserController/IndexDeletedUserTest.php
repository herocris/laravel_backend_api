<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDeletedUserTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
    }

    #[Test]
    public function show_index_deleted_successfully(): void
    {
        $users = User::factory()->count(10)->create();
        $users->each->delete(); //para borrar los usuarios creados

        $response = $this->getJson(route('user.indexDeleted'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'nombre',
                    'correo',
                    'roles',
                    'permisos',
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
    public function show_empty_data_if_not_deleted_users(): void
    {
        $response = $this->getJson(route('user.indexDeleted'));
        $response->assertOk();
        $response->assertExactJson([            
            'data' => [],
        ]);
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_cant_show_index_deleted_returns_401(): void
    {
        Auth::logout();

        $response = $this->getJson(route('user.indexDeleted'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
