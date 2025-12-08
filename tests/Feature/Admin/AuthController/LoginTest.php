<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{

    private $user_data = [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    #[Test]
    public function status_ok(): void
    {
        $response = $this->postJson(route('auth.login'), $this->user_data);
        $response->assertOk();
    }

    #[Test]
    public function user_is_login_after_login(): void
    {
        $this->postJson(route('auth.login'), $this->user_data);
        $this->assertTrue(Auth::check());
    }

    #[Test]
    public function correct_json_content(): void
    {
        $response = $this->postJson(route('auth.login'), $this->user_data);
        $response->assertExactJson([
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

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $response = $this->postJson(route('auth.login'), [
            'email' => $this->user_data['email'],
            'password' => 'wrongPassword',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Credenciales incorrectas'
            ]);
    }

    #[Test]
    public function login_fails_with_non_existing_email(): void
    {
        $response = $this->postJson(route('auth.login'), [
            'email' => 'noexists@test.com',
            'password' => $this->user_data['password'],
        ]);
        $response->assertStatus(401)->assertJson(['error' => 'Credenciales incorrectas']);
    }

    public static function invalidLoginDataProvider(): array
    {
        $invalidData = [
            'email' => 'usuario@prueba.com',
            'password' => 'password123',
        ];
        return [
            'missing email' => [
                array_diff_key($invalidData, ['email' => '']), //array_diff_key compara las claves de los arrays y elimina las que coinciden
                'email',
                'The email field is required.'
            ],
            'invalid email' => [
                array_merge($invalidData, ['email' => 'invalid-email']), //array_merge sobre escribe el valor de una clave
                'email',
                'The email field must be a valid email address.'
            ],
            'missing password' => [
                array_diff_key($invalidData, ['password' => '']),
                'password',
                'The password field is required.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidLoginDataProvider')]
    public function status_422_to_login(array $data, string $error_field, string $error_message): void
    {
        $response = $this->postJson(route('auth.login'), $data);
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
    public function login_sets_cookie_with_jwt(): void
    {
        $response = $this->postJson(route('auth.login'), $this->user_data);
        $response->assertCookie('token');       // Cookie existe
        $response->assertCookieNotExpired('token');
    }
}
