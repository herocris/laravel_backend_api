<?php

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowAmmunitionConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;
    private Ammunition $ammunition;
    private AmmunitionConfiscation $ammunitionConfiscation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        $this->ammunition = Ammunition::factory()->create();
        $this->ammunitionConfiscation = AmmunitionConfiscation::factory()->create();
    }

    #[Test]
    public function show_returns_single_ammunition_confiscation()
    {
        $response = $this->getJson(route('ammunitionConfiscation.show', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->ammunitionConfiscation->id,
            'cantidad' => $this->ammunitionConfiscation->amount,
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'municion' => [
                'identificador' => $this->ammunition->id,
                'descripcion' => $this->ammunition->description,
            ],
            'foto' => $this->ammunitionConfiscation->photo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_ammunition_confiscation(): void
    {
        $response = $this->deleteJson(route('ammunitionConfiscation.destroy', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertOk();
        $response = $this->getJson(route('ammunitionConfiscation.show', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunitionconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('ammunitionConfiscation.show', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
