<?php

namespace Tests\Feature\Admin\DrugController;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDeletedDrugTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->grantPermissions($this->user, ['drug.view']);
        $drugs = Drug::factory()->count(10)->create();
        $drugs->each->delete(); // para borrar los permisos creados
    }

    #[Test]
    public function index_deleted_returns_only_soft_deleted_drugs()
    {
        $response = $this->getJson(route('drug.indexDeleted'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'descripcion',
                    'logo',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);
        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function index_deleted_returns_empty_when_no_deleted_drugs()
    {
        Drug::onlyTrashed()->get()->each->restore();

        $response = $this->getJson(route('drug.indexDeleted'));
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
    public function unauthenticated_index_deleted_returns_401()
    {
        Auth::logout();
        $response = $this->getJson(route('drug.indexDeleted'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
