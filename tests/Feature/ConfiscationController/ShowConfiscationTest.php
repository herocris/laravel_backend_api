<?php

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
    }

    #[Test]
    public function show_returns_single_confiscation()
    {
        $response = $this->getJson(route('confiscation.show', ['confiscation' => $this->confiscation->id]));

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
    }

    #[Test]
    public function show_404_to_inactive_confiscation(): void
    {
        $response = $this->deleteJson(route('confiscation.destroy', ['confiscation' => $this->confiscation->id]));
        $response->assertOk();
        $response = $this->getJson(route('confiscation.show', ['confiscation' => $this->confiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo confiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('confiscation.show', ['confiscation' => $this->confiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
