<?php

namespace Tests\Feature\Admin\WeaponController;

use App\Models\Weapon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteWeaponTest extends TestCase
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
            'logo' => 'weapon_logo.png'
        ]);
    }

    #[Test]
    public function delete_soft_deletes_weapon()
    {
        $response = $this->deleteJson(route('weapon.destroy', $this->weapon->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->weapon->id,
            'descripcion' => $this->weapon->description,
            'logo' => $this->weapon->logo,
        ]);
        $this->assertSoftDeleted('weapons', ['id' => $this->weapon->id]);
    }

    #[Test]
    public function delete_deleted_weapon(): void
    {
        $response = $this->deleteJson(route('weapon.destroy', ['weapon' => $this->weapon->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('weapon.destroy', ['weapon' => $this->weapon->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo weapon",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('weapon.destroy', ['weapon' => $this->weapon->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
