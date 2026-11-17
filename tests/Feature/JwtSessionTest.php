<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PreventRequestForgery::class, fn ($app): PreventRequestForgery => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    public function test_login_cookie_authenticates_independent_requests_and_does_not_leak_to_another_client(): void
    {
        $user = User::factory()->create();
        $response = $this->login($user);
        $cookies = $this->cookies($response);

        $this->request('GET', '/api/me', cookies: $cookies)->assertOk()->assertJsonPath('id', $user->id);
        $this->request('GET', '/api/me')->assertUnauthorized();
        $this->request('GET', '/api/me', headers: ['Authorization' => 'Bearer '.$cookies['token']])
            ->assertOk()->assertJsonPath('id', $user->id)->assertCookieMissing(config('session.cookie'));
    }

    public function test_cookie_and_json_expire_with_the_signed_jwt_and_environment_attributes(): void
    {
        $this->freezeTime();
        config(['jwt.ttl' => 7, 'session.secure' => true, 'session.same_site' => 'none', 'session.domain' => 'example.test']);
        $response = $this->login(User::factory()->create());
        $cookie = $response->getCookie('token', false);

        $response->assertJsonPath('expires_in', 420);
        $this->assertSame(now()->addMinutes(7)->timestamp, $cookie->getExpiresTime());
        $this->assertSame('example.test', $cookie->getDomain());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('none', $cookie->getSameSite());
        $this->assertSame($cookie->getExpiresTime(), Auth::setToken($cookie->getValue())->payload()->get('exp'));
    }

    public function test_expired_signed_token_returns_401_for_profile_and_refresh(): void
    {
        $this->freezeTime();
        config(['jwt.ttl' => 2]);
        $cookies = $this->cookies($this->login(User::factory()->create()));
        $this->travel(121)->seconds();

        $this->request('GET', '/api/me', cookies: $cookies)->assertUnauthorized();
        $this->request('POST', '/api/refresh', headers: $this->csrfHeader($cookies), cookies: $cookies)->assertUnauthorized();
    }

    public function test_refresh_rotates_token_revokes_old_token_and_preserves_permissions(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drug.view']);
        $cookies = $this->cookies($this->login($user));
        $this->travel(1)->minutes();

        $response = $this->request('POST', '/api/refresh', headers: $this->csrfHeader($cookies), cookies: $cookies);

        $response->assertOk()->assertJsonPath('expires_in', 3600)->assertJsonPath('permissions', ['drug.view']);
        $rotated = $this->cookies($response);
        $this->assertNotSame($cookies['token'], $rotated['token']);
        $this->request('GET', '/api/me', cookies: $cookies)->assertUnauthorized();
        $this->request('POST', '/api/refresh', headers: $this->csrfHeader($cookies), cookies: $cookies)->assertUnauthorized();
        $this->request('GET', '/api/drug', cookies: $rotated)->assertOk();

        $this->travel(1)->minutes();
        $next = $this->request('POST', '/api/refresh', headers: $this->csrfHeader($rotated), cookies: $rotated);
        $next->assertOk();
        $this->assertNotSame($rotated['token'], $next->getCookie('token', false)->getValue());
    }

    public function test_refresh_window_is_measured_from_original_login_even_after_rotation(): void
    {
        $this->freezeTime();
        config(['jwt.ttl' => 60, 'jwt.refresh_ttl' => 5]);
        $cookies = $this->cookies($this->login(User::factory()->create()));
        $this->travel(4)->minutes();
        $rotated = $this->cookies($this->request('POST', '/api/refresh', headers: $this->csrfHeader($cookies), cookies: $cookies)->assertOk());
        $this->travel(2)->minutes();

        $this->request('GET', '/api/me', cookies: $rotated)->assertOk();
        $this->request('POST', '/api/refresh', headers: $this->csrfHeader($rotated), cookies: $rotated)
            ->assertUnauthorized()->assertJsonPath('code', 401);
    }

    public function test_logout_clears_matching_cookie_and_revokes_only_the_current_token(): void
    {
        config(['session.path' => '/api', 'session.domain' => 'example.test']);
        $user = User::factory()->create();
        $cookies = $this->cookies($this->login($user));
        $otherClient = $this->cookies($this->login($user));

        $response = $this->request('POST', '/api/logout', headers: $this->csrfHeader($cookies), cookies: $cookies);

        $response->assertOk()->assertCookieExpired('token');
        $this->assertSame('/api', $response->getCookie('token', false)->getPath());
        $this->assertSame('example.test', $response->getCookie('token', false)->getDomain());
        $this->request('GET', '/api/me', cookies: $cookies)->assertUnauthorized();
        $this->request('GET', '/api/me', cookies: $otherClient)->assertOk();
        $this->assertDatabaseHas('activity_log', ['event' => 'logout', 'causer_id' => $user->id]);
    }

    /** @return array<string, array{string}> */
    public static function sessionMutations(): array
    {
        return ['logout' => ['/api/logout'], 'refresh' => ['/api/refresh'], 'create drug' => ['/api/drug']];
    }

    #[DataProvider('sessionMutations')]
    public function test_cookie_mutations_without_csrf_return_419_and_leave_token_valid(string $uri): void
    {
        $cookies = $this->cookies($this->login(User::factory()->create()));

        $this->request('POST', $uri, cookies: $cookies)->assertStatus(419)->assertJsonPath('code', 419);

        $this->request('GET', '/api/me', cookies: $cookies)->assertOk();
        $this->assertDatabaseMissing('activity_log', ['event' => 'logout']);
        $this->assertDatabaseCount('drugs', 0);
    }

    public function test_csrf_token_from_another_session_returns_419(): void
    {
        $cookies = $this->cookies($this->login(User::factory()->create()));
        $otherSession = $this->cookies($this->request('GET', '/api/csrf-cookie'));

        $this->request('POST', '/api/logout', headers: $this->csrfHeader($otherSession), cookies: $cookies)->assertStatus(419);
        $this->request('GET', '/api/me', cookies: $cookies)->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function publicMutations(): array
    {
        return ['login' => ['/api/login'], 'registration' => ['/api/register']];
    }

    #[DataProvider('publicMutations')]
    public function test_login_and_registration_require_csrf_even_with_an_arbitrary_bearer_header(string $uri): void
    {
        $this->request('POST', $uri, ['email' => 'user@example.test', 'password' => 'password', 'name' => 'User'], ['Authorization' => 'Bearer fake'])
            ->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_with_csrf_creates_unprivileged_cookie_session(): void
    {
        $cookies = $this->cookies($this->request('GET', '/api/csrf-cookie')->assertNoContent());

        $response = $this->request('POST', '/api/register', [
            'name' => 'New User', 'email' => 'new@example.test', 'password' => 'password', 'roles' => [1],
        ], $this->csrfHeader($cookies), $cookies);

        $response->assertOk()->assertJsonPath('roles', [])->assertJsonPath('permissions', []);
        $this->assertDatabaseHas('users', ['email' => 'new@example.test']);
        $this->request('GET', '/api/me', cookies: $this->cookies($response))->assertOk()->assertJsonPath('email', 'new@example.test');
    }

    public function test_bearer_mutation_uses_header_identity_without_cookie_csrf_or_session(): void
    {
        $cookieUser = User::factory()->create();
        $cookies = $this->cookies($this->login($cookieUser));
        $bearerUser = User::factory()->create();
        $bearer = $this->cookies($this->login($bearerUser))['token'];

        $response = $this->request('POST', '/api/logout', headers: ['Authorization' => 'Bearer '.$bearer], cookies: $cookies);

        $response->assertOk()->assertCookieMissing(config('session.cookie'));
        $this->assertDatabaseHas('activity_log', ['event' => 'logout', 'causer_id' => $bearerUser->id]);
        $this->request('GET', '/api/me', headers: ['Authorization' => 'Bearer '.$bearer])->assertUnauthorized();
        $this->request('GET', '/api/me', cookies: $cookies)->assertOk()->assertJsonPath('id', $cookieUser->id);
        $this->request('GET', '/api/me', headers: ['Authorization' => 'Bearer invalid'], cookies: $cookies)->assertUnauthorized();
    }

    public function test_tokens_in_query_or_body_are_not_authentication_credentials(): void
    {
        $token = $this->cookies($this->login(User::factory()->create()))['token'];

        $this->request('GET', '/api/me?token='.$token)->assertUnauthorized();
        $this->request('POST', '/api/logout', ['token' => $token])->assertUnauthorized();
    }

    public function test_internal_jwt_failures_return_500_without_exposing_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-jwt-error', function (): never {
            throw new JWTException('Internal signing configuration');
        });

        $this->request('GET', '/api/test-jwt-error')->assertInternalServerError()->assertExactJson([
            'error' => 'Error interno del servidor', 'code' => 500,
        ]);
    }

    public function test_cors_accepts_only_configured_origins_with_credentials_and_csrf_headers(): void
    {
        config(['cors.allowed_origins' => ['https://client.example.test', 'https://second.example.test']]);

        $this->request('OPTIONS', '/api/login', headers: [
            'Origin' => 'https://client.example.test', 'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://client.example.test')
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertHeader('Access-Control-Allow-Headers', 'content-type,x-xsrf-token');
        $this->request('OPTIONS', '/api/login', headers: [
            'Origin' => 'https://untrusted.example.test', 'Access-Control-Request-Method' => 'POST',
        ])->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    private function login(User $user): TestResponse
    {
        $cookies = $this->cookies($this->request('GET', '/api/csrf-cookie')->assertNoContent());

        return $this->request('POST', '/api/login', ['email' => $user->email, 'password' => 'password'], $this->csrfHeader($cookies), $cookies)->assertOk();
    }

    /** @return array<string, string> */
    private function cookies(TestResponse $response): array
    {
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    /**
     * @param  array<string, string>  $cookies
     * @return array<string, string>
     */
    private function csrfHeader(array $cookies): array
    {
        return ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $cookies
     */
    private function request(string $method, string $uri, array $data = [], array $headers = [], array $cookies = []): TestResponse
    {
        $this->resetJwtAuthentication();
        app('session.store')->flush();
        $this->unencryptedCookies = $cookies;
        $this->withCredentials();

        return $this->json($method, $uri, $data, $headers);
    }
}
