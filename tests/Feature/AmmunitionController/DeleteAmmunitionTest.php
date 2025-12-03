<?php

namespace Tests\Feature\Admin\AmmunitionController;

use App\Models\Ammunition;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteAmmunitionTest extends TestCase
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
            'logo' => 'ammunition_logo.png'
        ]);
    }

    #[Test]
    public function delete_soft_deletes_ammunition()
    {
        $response = $this->deleteJson(route('ammunition.destroy', $this->ammunition->id));
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->ammunition->id,
            'descripcion' => $this->ammunition->description,
            'logo' => $this->ammunition->logo,
        ]);
        $this->assertSoftDeleted('ammunitions', ['id' => $this->ammunition->id]);
    }

    #[Test]
    public function delete_deleted_ammunition(): void
    {
        $response = $this->deleteJson(route('ammunition.destroy', ['ammunition' => $this->ammunition->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('ammunition.destroy', ['ammunition' => $this->ammunition->id]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo ammunition",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401()
    {
        Auth::logout();

        $response = $this->deleteJson(route('ammunition.destroy', ['ammunition' => $this->ammunition->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
