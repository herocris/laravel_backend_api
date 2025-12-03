<?php

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteAmmunitionConfiscationTest extends TestCase
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
    public function delete_ammunitionConfiscation()
    {
        $response = $this->deleteJson(route('ammunitionConfiscation.destroy', $this->ammunitionConfiscation->id));
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
        $this->assertSoftDeleted('ammunition_confiscations', ['id' => $this->ammunitionConfiscation->id]);
    }

    #[Test]
    public function delete_deleted_ammunitionConfiscation(): void
    {
        $response = $this->deleteJson(route('ammunitionConfiscation.destroy', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('ammunitionConfiscation.destroy', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunitionconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('ammunitionConfiscation.destroy', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
