<?php

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexAmmunitionConfiscationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        Confiscation::factory()->create();
        Ammunition::factory()->create();
    }

    #[Test]
    public function index_returns_all_ammunitionConfiscations()
    {
        AmmunitionConfiscation::factory()->count(10)->create();

        $response = $this->getJson(route('ammunitionConfiscation.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'cantidad',
                    'decomiso' => [
                        'identificador',
                        'observacion',
                    ],
                    'municion' => [
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
    public function empty_ammunitionConfiscation_collection(): void
    {
        $response = $this->getJson(route('ammunitionConfiscation.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('ammunitionConfiscation.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
