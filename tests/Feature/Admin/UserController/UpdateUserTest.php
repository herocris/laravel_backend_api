<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateUserTest extends TestCase
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
        User::factory()->create([
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => Hash::make('secondPass'),
        ]);
    }

    #[Test]
    public function update_user_successfully(): void
    {
        $payload = [
            'nombre' => 'Nombre Actualizado',
            'correo' => 'original@example.com', // mismo email; debe permitir
            'password' => 'newSecret123',
        ];

        $response = $this->putJson(route('user.update', ['user' => $this->user->id]), $payload);
        $response->assertOk();

        $response->assertJson([
            'identificador' => $this->user->id,
            'nombre' => 'Nombre Actualizado',
            'correo' => 'original@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Nombre Actualizado',
            'email' => 'original@example.com',
        ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newSecret123', $this->user->password));
    }

    public static function invalidUpdateDataProvider(): array
    {
        // Create a second user for uniqueness test

        $invalidData = [
            'nombre' => 'Nombre Actualizado',
            'correo' => 'original@example.com',
            'password' => 'newSecret123',
        ];

        return [
            'unique email' => [
                array_merge($invalidData, ['correo' => 'second@example.com']),
                'correo',
                'The correo has already been taken.'
            ],
            'missing nombre' => [
                array_diff_key($invalidData, ['nombre' => '']),
                'nombre',
                'The nombre field is required.'
            ],
            'missing correo' => [
                array_diff_key($invalidData, ['correo' => '']),
                'correo',
                'The correo field is required.'
            ],
            'missing password' => [
                array_diff_key($invalidData, ['password' => '']),
                'password',
                'The password field is required.'
            ],
            'invalid correo format' => [
                array_merge($invalidData, ['correo' => 'not-an-email']),
                'correo',
                'The correo field must be a valid correo address.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidUpdateDataProvider')]
    public function status_422_to_update_user(array $data, string $error_field, string $error_message): void
    {
        $response = $this->putJson(route('user.update', ['user' => $this->user->id]), $data);
        $response->assertUnprocessable();
        $response->assertExactJson([
            "error" => [
                $error_field => [
                    $error_message
                ]
            ],
            "code" => 422
        ]);
    }

    #[Test]
    public function unauthenticated_update_returns_401(): void
    {
        Auth::logout();
        $payload = [
            'nombre' => 'Nombre Actualizado',
            'correo' => 'original@example.com',
            'password' => 'newSecret123',
        ];
        $response = $this->putJson(route('user.update', ['user' => $this->user->id]), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
