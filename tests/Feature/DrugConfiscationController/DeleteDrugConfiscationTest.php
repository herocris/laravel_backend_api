<?php

namespace Tests\Feature\Admin\DrugConfiscationController;

use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\Confiscation;
use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteDrugConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;
    private Drug $drug;
    private DrugPresentation $drugPresentation;
    private DrugConfiscation $drugConfiscation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        $this->drug = Drug::factory()->create();
        $this->drugPresentation = DrugPresentation::factory()->create();
        $this->drugConfiscation = DrugConfiscation::factory()->create();
    }

    #[Test]
    public function delete_drugConfiscation()
    {
        $response = $this->deleteJson(route('drugConfiscation.destroy', $this->drugConfiscation->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drugConfiscation->id,
            'cantidad' => $this->drugConfiscation->amount,
            'peso' => $this->drugConfiscation->weight,
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'droga' => [
                'identificador' => $this->drug->id,
                'descripcion' => $this->drug->description,
            ],
            'presentacion' => [
                'identificador' => $this->drugPresentation->id,
                'descripcion' => $this->drugPresentation->description,
            ],
            'foto' => $this->drugConfiscation->photo,
        ]);
        $this->assertSoftDeleted('drug_confiscations', ['id' => $this->drugConfiscation->id]);
    }

    #[Test]
    public function delete_deleted_drugConfiscation(): void
    {
        $response = $this->deleteJson(route('drugConfiscation.destroy', ['drugConfiscation' => $this->drugConfiscation->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('drugConfiscation.destroy', ['drugConfiscation' => $this->drugConfiscation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drugconfiscation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('drugConfiscation.destroy', ['drugConfiscation' => $this->drugConfiscation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
