<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteUserTest extends TestCase
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
    }

    #[Test]
    public function delete_user_successfully(): void
    {

        $response = $this->deleteJson(route('user.destroy', ['user' => $this->user->id]));
        $response->assertOk();

        $response->assertJson([
            'identificador' => $this->user->id,
            'nombre' => $this->user->name,
            'correo' => $this->user->email,
        ]);

        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
    }
    #[Test]
    public function delete_deleted_user(): void
    {
        $response = $this->deleteJson(route('user.destroy', ['user' => $this->user->id]));
        $response->assertOk();
        $response = $this->deleteJson(route('user.destroy', ['user' => $this->user->id]));
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
