<?php

namespace Tests\Feature\Admin\DrugController;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreDrugTest extends TestCase
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
            'logo' => 'logo_test.png',
        ]);
        $this->drug->delete();
    }

    #[Test]
    public function restore_recovers_drug_successfully()
    {
        $this->assertSoftDeleted('drugs', ['id' => $this->drug->id]);

        $response = $this->postJson(route('drug.restore', ['drug' => $this->drug->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->drug->id,
            'descripcion' => $this->drug->description,
            'logo' => $this->drug->logo,
        ]);
        
        $this->assertDatabaseHas('drugs', [
            'id' => $this->drug->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_drug(): void
    {       
        $response = $this->postJson(route('drug.restore', ['drug' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo drug",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('drug.restore', ['drug' => $this->drug->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
