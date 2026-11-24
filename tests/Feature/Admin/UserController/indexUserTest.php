<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class indexUserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->grantPermissions($this->user, ['user.view']);
    }

    #[Test]
    public function index_returns_collection_if_exists(): void
    {
        User::factory()->count(10)->create();
        $response = $this->getJson(route('user.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'nombre',
                    'correo',
                    'roles',
                    'permisos',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);

        $response->assertJsonCount(10, 'data');
    }

    // este test falla si hay otros usuarios en la base de datos aparte del admin
    #[Test]
    public function index_if_only_admin_user_exists(): void
    {
        $response = $this->getJson(route('user.index'));
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
