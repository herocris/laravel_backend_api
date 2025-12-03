<?php

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        $this->confiscation->delete();
    }

    #[Test]
    public function restore_recovers_confiscation_successfully()
    {
        $this->assertSoftDeleted('confiscations', ['id' => $this->confiscation->id]);

        $response = $this->postJson(route('confiscation.restore', ['confiscation' => $this->confiscation->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->confiscation->id,
            'fecha' => $this->confiscation->date,
            'observacion' => $this->confiscation->observation,
            'direccion' => $this->confiscation->direction,
            'departamento' => $this->confiscation->department,
            'municipalidad' => $this->confiscation->municipality,
            'latitud' => $this->confiscation->latitude,
            'longitud' => $this->confiscation->length,
        ]);

        $this->assertDatabaseHas('confiscations', [
            'id' => $this->confiscation->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function not_restore_non_existent_confiscation(): void
    {
        $response = $this->postJson(route('confiscation.restore', ['confiscation' => 829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo confiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('confiscation.restore', ['confiscation' => $this->confiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
