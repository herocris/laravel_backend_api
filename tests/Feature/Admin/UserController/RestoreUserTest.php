<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreUserTest extends TestCase
{
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('originalPass'),
        ]);
        $this->user->delete();
    }

    #[Test]
    public function restore_user_successfully(): void
    {
        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
        $response = $this->postJson(route('user.restore', ['user' => $this->user->id]));
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $this->user->id,
            'nombre' => $this->user->name,
            'correo' => $this->user->email,
            'roles' => [],
            'permisos' => [],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'deleted_at' => null,
        ]);
    }
    #[Test]
    public function restore_non_existent_user(): void
    {
        $response = $this->postJson(route('user.restore', ['user' => 4567]));
        $response->assertStatus(404)->assertJson([
            'error' => "No existe el recurso con ese id para el modelo user",
            'code' => 404,
        ]);
    }

    #[Test]
    public function unauthenticated_delete_returns_401(): void
    {
        Auth::logout();

        $response = $this->deleteJson(route('user.destroy', ['user' => $this->user->id]));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
