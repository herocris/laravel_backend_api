<?php

namespace Tests\Feature\Admin\DrugPresentationController;

use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowDrugPresentationTest extends TestCase
{
    private User $user;
    private DrugPresentation $drugPresentation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->drugPresentation = DrugPresentation::factory()->create(['description' => 'test_drugPresentation', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function show_returns_single_drugPresentation()
    {
        $response = $this->getJson(route('drugPresentation.show', ['drugPresentation' => $this->drugPresentation->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drugPresentation->id,
            'descripcion' => $this->drugPresentation->description,
            'logo' => $this->drugPresentation->logo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_drugPresentation(): void
    {
        $response = $this->deleteJson(route('drugPresentation.destroy', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertOk();
        $response = $this->getJson(route('drugPresentation.show', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drugpresentation",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('drugPresentation.show', ['drugPresentation' => $this->drugPresentation->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
