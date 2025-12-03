<?php

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteConfiscationTest extends TestCase
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
    public function delete_confiscation()
    {
        $response = $this->deleteJson(route('confiscation.destroy', $this->confiscation->id));
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
        $this->assertSoftDeleted('confiscations', ['id' => $this->confiscation->id]);
    }

    #[Test]
    public function delete_deleted_confiscation(): void
    {
        $response = $this->deleteJson(route('confiscation.destroy', ['confiscation' => $this->confiscation->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('confiscation.destroy', ['confiscation' => $this->confiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo confiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('confiscation.destroy', ['confiscation' => $this->confiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
