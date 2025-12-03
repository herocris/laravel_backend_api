<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AuthController;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
    }

    #[Test]
    public function me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson(route('auth.me'));
        // En el proyecto, rutas protegidas devuelven 401 con JSON { code, error }
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }

    #[Test]
    public function me_returns_status_ok_when_authenticated(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('auth.me'));

        $response->assertOk();
    }

    #[Test]
    public function me_returns_correct_user_data(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('auth.me'));

        $response->assertJson([
            'name' => $this->user['name'],
            'email' => $this->user['email'],
        ]);
    }

    #[Test]
    public function me_hides_sensitive_fields(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('auth.me'));

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    #[Test]
    public function me_returns_json_content_type(): void
    {
        $token = Auth::login($this->user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('auth.me'));

        $response->assertHeader('Content-Type', 'application/json');
    }
}
