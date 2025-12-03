<?php

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDeletedConfiscationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $confiscations = Confiscation::factory()->count(10)->create();
        $confiscations->each->delete(); //para borrar los deocomisos creados
    }

    #[Test]
    public function index_deleted_returns_only_soft_deleted_confiscations()
    {
        $response = $this->getJson(route('confiscation.indexDeleted'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'fecha',
                    'observacion',
                    'direccion',
                    'departamento',
                    'municipalidad',
                    'latitud',
                    'longitud',
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
    public function index_deleted_returns_empty_when_no_deleted_confiscations()
    {
        Confiscation::onlyTrashed()->get()->each->restore();

        $response = $this->getJson(route('confiscation.indexDeleted'));
        $response->assertOk();
        $response->assertExactJson([
            'data' => [],
        ]);
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_deleted_returns_401()
    {
        Auth::logout();
        $response = $this->getJson(route('confiscation.indexDeleted'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
