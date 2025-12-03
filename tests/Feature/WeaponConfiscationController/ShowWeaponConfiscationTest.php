<?php

namespace Tests\Feature\Admin\WeaponConfiscationController;

use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowWeaponConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;
    private Weapon $weapon;
    private WeaponConfiscation $weaponConfiscation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        $this->weapon = Weapon::factory()->create();
        $this->weaponConfiscation = WeaponConfiscation::factory()->create();
    }

    #[Test]
    public function show_returns_single_ammunition_confiscation()
    {
        $response = $this->getJson(route('weaponConfiscation.show', ['weaponConfiscation' => $this->weaponConfiscation->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->weaponConfiscation->id,
            'cantidad' => $this->weaponConfiscation->amount,
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'arma' => [
                'identificador' => $this->weapon->id,
                'descripcion' => $this->weapon->description,
            ],
            'foto' => $this->weaponConfiscation->photo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_ammunition_confiscation(): void
    {
        $response = $this->deleteJson(route('weaponConfiscation.destroy', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertOk();
        $response = $this->getJson(route('weaponConfiscation.show', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo weaponconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('weaponConfiscation.show', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
