<?php

namespace Tests\Feature\Admin\DrugController;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowDrugTest extends TestCase
{
    private User $user;
    private Drug $drug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->drug = Drug::factory()->create(['description' => 'test_drug', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function show_returns_single_drug()
    {
        $response = $this->getJson(route('drug.show', ['drug' => $this->drug->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drug->id,
            'descripcion' => $this->drug->description,
            'logo' => $this->drug->logo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_drug(): void
    {
        $response = $this->deleteJson(route('drug.destroy', ['drug' => $this->drug->id]));
        $response->assertOk();
        $response = $this->getJson(route('drug.show', ['drug' => $this->drug->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drug",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('drug.show', ['drug' => $this->drug->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
