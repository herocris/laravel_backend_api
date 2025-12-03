<?php

namespace Tests\Feature\Admin\DrugController;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteDrugTest extends TestCase
{
    private User $user;
    private Drug $drug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->drug = Drug::factory()->create([
            'description' => 'drug_test',
            'logo' => 'drug_logo.png'
        ]);
    }

    #[Test]
    public function delete_drug()
    {
        $response = $this->deleteJson(route('drug.destroy', $this->drug->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drug->id,
            'descripcion' => $this->drug->description,
            'logo' => $this->drug->logo,
        ]);
        $this->assertSoftDeleted('drugs', ['id' => $this->drug->id]);
    }

    #[Test]
    public function delete_deleted_drug(): void
    {
        $response = $this->deleteJson(route('drug.destroy', ['drug' => $this->drug->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('drug.destroy', ['drug' => $this->drug->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drug",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('drug.destroy', ['drug' => $this->drug->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
