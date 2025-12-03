<?php

namespace Tests\Feature\Admin\WeaponConfiscationController;

use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteWeaponConfiscationTest extends TestCase
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
    public function delete_weaponConfiscation()
    {
        $response = $this->deleteJson(route('weaponConfiscation.destroy', $this->weaponConfiscation->id));
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
        $this->assertSoftDeleted('weapon_confiscations', ['id' => $this->weaponConfiscation->id]);
    }

    #[Test]
    public function delete_deleted_weaponConfiscation(): void
    {
        $response = $this->deleteJson(route('weaponConfiscation.destroy', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('weaponConfiscation.destroy', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo weaponconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('weaponConfiscation.destroy', ['weaponConfiscation' => $this->weaponConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
