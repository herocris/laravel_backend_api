<?php

namespace Tests\Feature;

use App\Models\Activitylog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthenticationEventsTest extends TestCase
{
    public function test_login_records_one_event_with_existing_description_and_safe_request_context(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);

        $this->withHeaders(['User-Agent' => 'Audit test'])->postJson('/api/login', [
            'email' => $user->email, 'password' => 'test-password',
        ])->assertOk()->assertCookie('token');

        $event = Activitylog::where('event', 'login')->sole();
        $this->assertSame($user->id, $event->causer_id);
        $this->assertSame($user->name, $event->log_name);
        $this->assertSame("El usuario {$user->name} ha iniciado sesión", $event->description);
        $this->assertSame([
            'guard' => 'api', 'ip' => '127.0.0.1', 'user_agent' => 'Audit test',
            'name' => $user->name, 'email' => $user->email,
        ], $event->properties->all());
    }

    public function test_registration_records_one_login_event(): void
    {
        $this->postJson('/api/register', [
            'name' => 'New user', 'email' => 'new@example.com', 'password' => 'test-password',
        ])->assertOk()->assertCookie('token')->assertJsonPath('roles', [])->assertJsonPath('permissions', []);

        $user = User::where('email', 'new@example.com')->sole();
        $this->assertSame($user->id, Activitylog::where('event', 'login')->sole()->causer_id);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_invalid_credentials_return_401_and_record_failure_without_secrets(bool $existingUser): void
    {
        $user = $existingUser ? User::factory()->create() : null;
        $email = $user?->email ?? 'unknown@example.com';

        $this->postJson('/api/login', ['email' => $email, 'password' => 'secret-failed-password'])
            ->assertUnauthorized()->assertExactJson(['error' => 'Credenciales incorrectas']);

        $event = Activitylog::where('event', 'failed')->sole();
        $this->assertSame($user?->id, $event->causer_id);
        $this->assertSame($user === null ? null : User::class, $event->causer_type);
        $this->assertSame($email, $event->properties['email']);
        $this->assertSame(['guard', 'ip', 'user_agent', 'email'], array_keys($event->properties->all()));
        $this->assertStringNotContainsString('secret-failed-password', $event->toJson());
        $this->assertSame(0, Activitylog::where('event', 'login')->count());
    }

    public function test_unknown_user_failure_does_not_inherit_an_authenticated_actor(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $this->postJson('/api/login', ['email' => 'unknown@example.com', 'password' => 'test-password'])->assertUnauthorized();

        $this->assertNull(Activitylog::where('event', 'failed')->sole()->causer_id);
    }

    public function test_invalid_input_returns_422_without_an_authentication_event(): void
    {
        $this->postJson('/api/login', ['email' => 'invalid'])->assertUnprocessable();

        $this->assertSame(0, Activitylog::whereIn('event', ['login', 'failed', 'logout'])->count());
    }

    public function test_logout_records_one_event_and_preserves_response_and_cookie_contract(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);
        $this->resetJwtAuthentication();

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout')
            ->assertOk()->assertExactJson(['message' => 'Logout successful'])->assertCookieExpired('token');

        $event = Activitylog::where('event', 'logout')->sole();
        $this->assertSame($user->id, $event->causer_id);
        $this->assertSame($user->name, $event->log_name);
        $this->assertSame("El usuario {$user->name} ha cerrado sesión", $event->description);
        $this->resetJwtAuthentication();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_unauthenticated_logout_returns_401_without_a_logout_event(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();

        $this->assertSame(0, Activitylog::where('event', 'logout')->count());
    }

    public function test_guard_operations_without_a_request_are_audited_and_refresh_is_not_a_new_login(): void
    {
        $user = User::factory()->create();
        $guard = Auth::guard('api');
        $request = $this->app['request'];
        $this->app->offsetUnset('request');

        try {
            $guard->login($user);
            $guard->setToken($guard->refresh());
            $guard->logout();
        } finally {
            $this->app->instance('request', $request);
        }

        $login = Activitylog::where('event', 'login')->sole();
        $this->assertNull($login->properties['ip']);
        $this->assertNull($login->properties['user_agent']);
        $this->assertSame($user->id, Activitylog::where('event', 'logout')->sole()->causer_id);
    }
}
