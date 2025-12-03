<?php

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreAmmunitionConfiscationTest extends TestCase
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
        $this->ammunitionConfiscation->delete();
    }

    #[Test]
    public function restore_recovers_ammunitionConfiscation_successfully()
    {
        $this->assertSoftDeleted('ammunition_confiscations', ['id' => $this->ammunitionConfiscation->id]);

        $response = $this->postJson(route('ammunitionConfiscation.restore', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));

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
        
        $this->assertDatabaseHas('ammunition_confiscations', [
            'id' => $this->ammunitionConfiscation->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_ammunitionConfiscation(): void
    {       
        $response = $this->postJson(route('ammunitionConfiscation.restore', ['ammunitionConfiscation' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunitionconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('ammunitionConfiscation.restore', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
