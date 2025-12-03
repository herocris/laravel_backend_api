<?php

namespace Tests\Feature\Admin\AmmunitionController;

use App\Models\Ammunition;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreAmmunitionTest extends TestCase
{
    private User $user;
    private Ammunition $ammunition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->ammunition = Ammunition::factory()->create([
            'description' => 'ammunition_test',
            'logo' => 'logo_test.png',
        ]);
        $this->ammunition->delete();
    }

    #[Test]
    public function restore_recovers_ammunition_successfully()
    {
        $this->assertSoftDeleted('ammunitions', ['id' => $this->ammunition->id]);

        $response = $this->postJson(route('ammunition.restore', ['ammunition' => $this->ammunition->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->ammunition->id,
            'descripcion' => $this->ammunition->description,
            'logo' => $this->ammunition->logo,
        ]);
        
        $this->assertDatabaseHas('ammunitions', [
            'id' => $this->ammunition->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_ammunition(): void
    {       
        $response = $this->postJson(route('ammunition.restore', ['ammunition' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunition",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('ammunition.restore', ['ammunition' => $this->ammunition->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
