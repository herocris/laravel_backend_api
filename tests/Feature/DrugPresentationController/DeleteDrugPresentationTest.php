<?php

namespace Tests\Feature\Admin\DrugPresentationController;

use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteDrugPresentationTest extends TestCase
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
            'logo' => 'drugPresentation_logo.png'
        ]);
    }

    #[Test]
    public function delete_drugPresentation()
    {
        $response = $this->deleteJson(route('drugPresentation.destroy', $this->drugPresentation->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drugPresentation->id,
            'descripcion' => $this->drugPresentation->description,
            'logo' => $this->drugPresentation->logo,
        ]);
        $this->assertSoftDeleted('drug_presentations', ['id' => $this->drugPresentation->id]);
    }

    #[Test]
    public function delete_deleted_drugPresentation(): void
    {
        $response = $this->deleteJson(route('drugPresentation.destroy', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('drugPresentation.destroy', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drugpresentation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('drugPresentation.destroy', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
