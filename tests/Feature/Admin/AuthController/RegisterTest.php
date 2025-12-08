<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;


class RegisterTest extends TestCase
{
    private $user_data = [
        'name' => 'Usuario de Prueba',
        'email' => 'usuario@prueba.com',
        'password' => 'password123',
    ];

    #[Test]
    public function status_ok(): void
    {
        $response = $this->postJson(route('auth.register'), $this->user_data);
        $response->assertOk();
    }
    #[Test]
    public function correct_save(): void
    {
        $this->postJson(route('auth.register'), $this->user_data);
        $last_user = User::latest()->first();
        $this->assertSame($this->user_data['name'], $last_user->name, 'El usuario no registró correctamente');
        $this->assertSame($this->user_data['email'], $last_user->email, 'El usuario no registró correctamente');
    }
    #[Test]
    public function correct_json_structure(): void
    {
        $response = $this->postJson(route('auth.register'), $this->user_data);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => [
                'name',
                'email'
            ],
            'roles',
            'permissions',
        ]);
    }
    #[Test]
    public function correct_json_content(): void
    {
        $response = $this->postJson(route('auth.register'), $this->user_data);
        $response->assertExactJson([
            'access_token' => $response['access_token'],
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user' => [
                'name' => $this->user_data['name'],
                'email' => $this->user_data['email'],
            ],
            'roles' => [],
            'permissions' => [],
        ]);
    }

    public static function invalidRegisterDataProvider(): array
    {
        $invalidData = [
            'name' => 'Usuario de Prueba',
            'email' => 'usuario@prueba.com',
            'password' => 'password123',
        ];
        return [
            'missing name' => [
                array_diff_key($invalidData, ['name' => '']),
                'name',
                'The name field is required.'
            ],
            'missing email' => [
                array_diff_key($invalidData, ['email' => '']),//array_diff_key compara las claves de los arrays y elimina las que coinciden
                'email',
                'The email field is required.'
            ],
            'unique email' => [
                array_merge($invalidData, ['email' => 'admin@yahoo.es']),
                'email',
                'The email has already been taken.'
            ],
            'invalid email' => [
                array_merge($invalidData, ['email' => 'invalid-email']),//array_merge sobre escribe el valor de una clave
                'email',
                'The email field must be a valid email address.'
            ],
            'missing password' => [
                array_diff_key($invalidData, ['password' => '']),
                'password',
                'The password field is required.'
            ],
            'invalid length password' => [
                array_merge($invalidData, ['password' => 'short']),
                'password',
                'The password field must be at least 6 characters.'
            ],
        ];
    }
    #[Test]
    #[DataProvider('invalidRegisterDataProvider')]
    public function status_422_to_register(array $data, string $error_field, string $error_message): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@yahoo.es',
            'password' => bcrypt('password'),
        ]);
        $response = $this->postJson(route('auth.register'), $data);
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
    public function token_belongs_to_registered_user()
    {
        $this->postJson(route('auth.register'), $this->user_data);
        $user = User::latest()->first();
        Auth::login($user);
        $this->assertTrue(Auth::user()->email === $this->user_data['email']);
    }
    #[Test]
    public function register_returns_json_on_validation_error()
    {
        $response = $this->postJson(route('auth.register'), []);
        $response->assertStatus(422)->assertHeader('Content-Type', 'application/json');
    }
    #[Test]
    public function no_user_created_when_validation_fails()
    {
        $response = $this->postJson(route('auth.register'), [
            'email' => 'usuario@prueba.com', // falta name y password
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('users', $this->user_data);
    }
    #[Test]
    public function user_is_login_after_register()
    {
        $this->postJson(route('auth.register'), $this->user_data);
        $this->assertTrue(Auth::check());
    }
}
