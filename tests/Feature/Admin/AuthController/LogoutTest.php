<?php

namespace Tests\Feature\Admin\AuthController;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;

class LogoutTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
    }

    #[Test]
    public function status_ok(): void
    {
        Auth::login($this->user);
        $response = $this->postJson(route('auth.logout'));
        $response->assertOk();
    }
    #[Test]
    public function correct_json_content(): void
    {
        Auth::login($this->user);
        $response = $this->postJson(route('auth.logout'));
        $response->assertExactJson([
            'message' => 'Logout successful',            
        ]);
    }
    #[Test]
    public function user_can_logout_successfully()
    {
        // Hacer login SOLO EN ESTE TEST
        Auth::login($this->user);
        // Validar que está autenticado antes del logout
        $this->assertTrue(Auth::check(), 'El usuario no estaba autenticado antes del logout');
        // Ejecutar logout
        $response = $this->postJson(route('auth.logout'));
        // Validar status
        $response->assertOk();
        // Validar JSON correcto
        $response->assertExactJson([
            'message' => 'Logout successful',
        ]);
        // Validar que YA NO está autenticado
        $this->assertFalse(Auth::check(), 'El usuario sigue autenticado después del logout');
    }

    #[Test]
    public function logout_sets_token_cookie_to_forget()
    {
        Auth::login($this->user); // Hacer login aquí
        $response = $this->postJson(route('auth.logout'));
        $response->assertCookieExpired('token');
        // Validar que la cookie es olvidada
    }

    #[Test]
    public function unauthenticated_user_cant_call_logout()
    {
        $response = $this->postJson(route('auth.logout'));

        // Usuario NO autenticado → debe devolver 401
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
