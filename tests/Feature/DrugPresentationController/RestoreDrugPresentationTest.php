<?php

namespace Tests\Feature\Admin\DrugPresentationController;

use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreDrugPresentationTest extends TestCase
{
    private User $user;
    private DrugPresentation $drugPresentation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->drugPresentation = DrugPresentation::factory()->create([
            'description' => 'drugPresentation_test',
            'logo' => 'logo_test.png',
        ]);
        $this->drugPresentation->delete();
    }

    #[Test]
    public function restore_recovers_drugPresentation_successfully()
    {
        $this->assertSoftDeleted('drug_presentations', ['id' => $this->drugPresentation->id]);

        $response = $this->postJson(route('drugPresentation.restore', ['drugPresentation' => $this->drugPresentation->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drugPresentation->id,
            'descripcion' => $this->drugPresentation->description,
            'logo' => $this->drugPresentation->logo,
        ]);
        
        $this->assertDatabaseHas('drug_presentations', [
            'id' => $this->drugPresentation->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_drugPresentation(): void
    {       
        $response = $this->postJson(route('drugPresentation.restore', ['drugPresentation' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drugpresentation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('drugPresentation.restore', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
