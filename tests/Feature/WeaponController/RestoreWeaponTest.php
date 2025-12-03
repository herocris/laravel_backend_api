<?php

namespace Tests\Feature\Admin\WeaponController;

use App\Models\Weapon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreWeaponTest extends TestCase
{
    private User $user;
    private Weapon $weapon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->weapon = Weapon::factory()->create([
            'description' => 'weapon_test',
            'logo' => 'logo_test.png',
        ]);
        $this->weapon->delete();
    }

    #[Test]
    public function restore_recovers_weapon_successfully()
    {
        $this->assertSoftDeleted('weapons', ['id' => $this->weapon->id]);

        $response = $this->postJson(route('weapon.restore', ['weapon' => $this->weapon->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->weapon->id,
            'descripcion' => $this->weapon->description,
            'logo' => $this->weapon->logo,
        ]);
        
        $this->assertDatabaseHas('weapons', [
            'id' => $this->weapon->id,
            'deleted_at' => null,
        ]);
    }

#[Test]
    public function not_restore_non_existent_weapon(): void
    {       
        $response = $this->postJson(route('weapon.restore', ['weapon' =>829]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo weapon",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_restore_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('weapon.restore', ['weapon' => $this->weapon->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
