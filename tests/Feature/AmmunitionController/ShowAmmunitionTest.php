<?php

namespace Tests\Feature\Admin\AmmunitionController;

use App\Models\Ammunition;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowAmmunitionTest extends TestCase
{
    private User $user;
    private Ammunition $ammunition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->ammunition = Ammunition::factory()->create(['description' => 'test_ammunition', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function show_returns_single_ammunition()
    {
        $response = $this->getJson(route('ammunition.show', ['ammunition' => $this->ammunition->id]));

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->ammunition->id,
            'descripcion' => $this->ammunition->description,
            'logo' => $this->ammunition->logo,
        ]);
    }

    #[Test]
    public function show_404_to_inactive_ammunition(): void
    {
        $response = $this->deleteJson(route('ammunition.destroy', ['ammunition' => $this->ammunition->id]));
        $response->assertOk();
        $response = $this->getJson(route('ammunition.show', ['ammunition' => $this->ammunition->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunition",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_show_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('ammunition.show', ['ammunition' => $this->ammunition->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
