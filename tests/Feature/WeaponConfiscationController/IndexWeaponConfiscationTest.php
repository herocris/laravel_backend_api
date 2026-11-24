<?php

namespace Tests\Feature\Admin\WeaponConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexWeaponConfiscationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->grantPermissions($this->user, ['weaponConfiscation.view']);
        Confiscation::factory()->create();
        Weapon::factory()->create();
    }

    #[Test]
    public function index_returns_all_weapon_confiscations()
    {
        WeaponConfiscation::factory()->count(10)->create();

        $response = $this->getJson(route('weaponConfiscation.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'cantidad',
                    'decomiso' => [
                        'identificador',
                        'observacion',
                    ],
                    'arma' => [
                        'identificador',
                        'descripcion',
                    ],
                    'foto',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);

        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function empty_weapon_confiscation_collection(): void
    {
        $response = $this->getJson(route('weaponConfiscation.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('weaponConfiscation.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
