<?php

namespace Tests\Feature\Admin\WeaponController;

use App\Models\Weapon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowWeaponTest extends TestCase
{
    private User $user;
    private Weapon $weapon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->weapon = Weapon::factory()->create(['description' => 'test_weapon', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function show_returns_single_weapon()
    {
        $response = $this->getJson(route('weapon.show', ['weapon' => $this->weapon->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->weapon->id,
            'descripcion' => $this->weapon->description,
            'logo' => $this->weapon->logo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_weapon(): void
    {
        $response = $this->deleteJson(route('weapon.destroy', ['weapon' => $this->weapon->id]));
        $response->assertOk();
        $response = $this->getJson(route('weapon.show', ['weapon' => $this->weapon->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo weapon",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('weapon.show', ['weapon' => $this->weapon->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
