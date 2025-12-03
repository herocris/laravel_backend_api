<?php

namespace Tests\Feature\Admin\DrugConfiscationController;

use App\Models\DrugConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDrugConfiscationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        Confiscation::factory()->create();
        Drug::factory()->create();
        DrugPresentation::factory()->create();
    }

    #[Test]
    public function index_returns_all_drugConfiscations()
    {
        DrugConfiscation::factory()->count(10)->create();

        $response = $this->getJson(route('drugConfiscation.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'cantidad',
                    'peso',
                    'decomiso' => [
                        'identificador',
                        'observacion',
                    ],
                    'droga' => [
                        'identificador',
                        'descripcion',
                    ],
                    'presentacion' => [
                        'identificador',
                        'descripcion',
                    ],
                    'foto',
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
    public function empty_drugConfiscation_collection(): void
    {
        $response = $this->getJson(route('drugConfiscation.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('drugConfiscation.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
