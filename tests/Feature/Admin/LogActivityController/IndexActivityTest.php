<?php

namespace Tests\Feature\Admin\LogActivityController;

use App\Models\Activitylog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexActivityTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->grantPermissions($this->user, ['activity.view']);
    }

    #[Test]
    public function index_returns_all_activities()
    {
        Activitylog::factory()->count(10)->create();

        $response = $this->getJson(route('activity.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'tipo_de_evento',
                    'descripcion',
                    'id_usuario',
                    'usuario',
                    'cambios',
                    'fecha',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);

        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('activity.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
