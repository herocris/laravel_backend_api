<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiRateLimitingTest extends TestCase
{
    public function test_malformed_login_fields_return_422_before_authentication(): void
    {
        $this->postJson('/api/login', ['email' => ['invalid'], 'password' => ['invalid']])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['email', 'password']]);
    }

    public function test_login_limits_normalized_account_and_ip_and_recovers_after_a_minute(): void
    {
        $this->freezeTime();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->loginAttempt('user@example.test')->assertUnauthorized();
        }

        $this->loginAttempt('USER@example.test')->assertTooManyRequests()->assertJsonPath('code', 429)
            ->assertHeader('Retry-After', '60')->assertHeader('X-RateLimit-Remaining', '0');
        $this->loginAttempt('other@example.test')->assertUnauthorized();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->loginAttempt('user@example.test')->assertUnauthorized();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->travel(61)->seconds();
        $this->loginAttempt('user@example.test')->assertUnauthorized();
    }

    public function test_login_ip_limit_cannot_be_evaded_by_changing_accounts(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->loginAttempt('user'.$attempt.'@example.test')->assertUnauthorized();
        }

        $this->loginAttempt('another@example.test')->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->loginAttempt('another@example.test')->assertUnauthorized();
    }

    public function test_registration_has_its_own_ip_quota_and_cannot_create_users_after_exhaustion(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/register', [])->assertUnprocessable();
        }

        $this->postJson('/api/register', ['name' => 'User', 'email' => 'user@example.test', 'password' => 'password'])->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
        $this->loginAttempt('user@example.test')->assertUnauthorized();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->postJson('/api/register', [])->assertUnprocessable();
    }

    public function test_api_quota_is_shared_by_tokens_of_one_user_and_isolated_from_other_users_and_session(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drug.view']);
        $token = Auth::login($user);
        $otherToken = Auth::login($user);
        $otherUser = User::factory()->create();
        $this->grantPermissions($otherUser, ['drug.view']);
        $otherUserToken = Auth::login($otherUser);
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->getWithToken('/api/drug', $token)->assertOk();
        }

        $this->getWithToken('/api/drug', $otherToken)->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->getWithToken('/api/drug', $token)->assertTooManyRequests();
        $this->getWithToken('/api/drug', $otherUserToken)->assertOk();
        $this->getWithToken('/api/me', $token)->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function statisticsEndpoints(): array
    {
        return [
            'drugs' => ['/api/drugGraphIndex'],
            'weapons' => ['/api/weaponGraphIndex'],
            'ammunition' => ['/api/ammunitionGraphIndex'],
            'map' => ['/api/confiscation/map'],
        ];
    }

    #[DataProvider('statisticsEndpoints')]
    public function test_statistics_share_a_separate_user_quota_without_consuming_queries_or_login(string $uri): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['graph.view', 'map.view', 'drug.view']);
        $token = Auth::login($user);
        $otherUser = User::factory()->create();
        $this->grantPermissions($otherUser, ['graph.view', 'map.view']);
        $otherToken = Auth::login($otherUser);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->getWithToken($uri, $token)->assertUnprocessable();
        }

        $this->getWithToken($uri, $token)->assertTooManyRequests()->assertHeader('X-RateLimit-Limit', '10');
        $this->getWithToken('/api/confiscation/map', $token)->assertTooManyRequests();
        $this->getWithToken($uri, $otherToken)->assertUnprocessable();
        $this->getWithToken('/api/drug', $token)->assertOk()->assertHeader('X-RateLimit-Remaining', '59');
        $this->resetJwtAuthentication();
        $this->loginAttempt($user->email)->assertUnauthorized();
    }

    public function test_session_quota_is_per_user_and_does_not_consume_api_quota(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drug.view']);
        $token = Auth::login($user);
        $otherToken = Auth::login(User::factory()->create());
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->getWithToken('/api/me', $token)->assertOk();
        }

        $this->getWithToken('/api/me', $token)->assertTooManyRequests();
        $this->getWithToken('/api/me', $otherToken)->assertOk();
        $this->getWithToken('/api/drug', $token)->assertOk()->assertHeader('X-RateLimit-Remaining', '59');
    }

    public function test_csrf_bootstrap_has_a_separate_ip_quota(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->getJson('/api/csrf-cookie')->assertNoContent();
        }

        $this->getJson('/api/csrf-cookie')->assertTooManyRequests();
        $this->loginAttempt('user@example.test')->assertUnauthorized();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->getJson('/api/csrf-cookie')->assertNoContent();
    }

    private function loginAttempt(string $email): TestResponse
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => 'incorrect']);
    }

    private function getWithToken(string $uri, string $token): TestResponse
    {
        $this->resetJwtAuthentication();

        return $this->getJson($uri, ['Authorization' => 'Bearer '.$token]);
    }
}
