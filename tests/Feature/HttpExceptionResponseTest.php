<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class HttpExceptionResponseTest extends TestCase
{
    #[Test]
    public function returns_401_for_an_unauthenticated_api_request(): void
    {
        $response = $this->getJson(route('auth.me'));

        $response->assertUnauthorized()->assertExactJson([
            'error' => 'No autenticado',
            'code' => 401,
        ]);
    }

    #[Test]
    public function returns_403_when_can_middleware_denies_a_permission(): void
    {
        $this->registerPermissionProtectedRoute();
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        Permission::factory()->create(['name' => 'test.errors.view', 'guard_name' => 'api']);

        $response = $this->getJson('/api/test-errors/protected');

        $response->assertForbidden()->assertExactJson([
            'error' => 'This action is unauthorized.',
            'code' => 403,
        ]);
    }

    #[Test]
    public function allows_a_user_with_the_explicit_permission(): void
    {
        $this->registerPermissionProtectedRoute();
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $permission = Permission::factory()->create(['name' => 'test.errors.view', 'guard_name' => 'api']);
        $user->givePermissionTo($permission);

        $response = $this->getJson('/api/test-errors/protected');

        $response->assertOk()->assertExactJson(['message' => 'Permitido']);
    }

    #[Test]
    public function returns_404_for_an_unknown_url(): void
    {
        $response = $this->getJson('/api/test-errors/unknown');

        $response->assertNotFound()->assertExactJson([
            'error' => 'Url no encontrada',
            'code' => 404,
        ]);
    }

    #[Test]
    public function returns_404_for_a_missing_bound_model(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson(route('drug.show', ['drug' => 999999]));

        $response->assertNotFound()->assertExactJson([
            'error' => 'No existe el recurso con ese id para el modelo drug',
            'code' => 404,
        ]);
    }

    #[Test]
    public function returns_405_with_the_allowed_methods_header(): void
    {
        Route::post('/api/test-errors/method', fn (): JsonResponse => response()->json([]));

        $response = $this->getJson('/api/test-errors/method');

        $response->assertMethodNotAllowed()->assertExactJson([
            'error' => 'Metodo no permitido',
            'code' => 405,
        ])->assertHeader('Allow', 'POST');
    }

    #[Test]
    public function returns_422_with_the_validation_errors(): void
    {
        Route::post('/api/test-errors/validation', function (Request $request): array {
            return $request->validate(['name' => ['required']], ['name.required' => 'El nombre es obligatorio.']);
        });

        $response = $this->postJson('/api/test-errors/validation', []);

        $response->assertUnprocessable()->assertExactJson([
            'error' => ['name' => ['El nombre es obligatorio.']],
            'code' => 422,
        ]);
    }

    #[Test]
    public function returns_422_when_assigning_a_missing_role(): void
    {
        Route::post('/api/test-errors/role', function (Request $request): void {
            $request->user()->assignRole('test-missing-role');
        })->middleware('auth');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/test-errors/role');

        $response->assertUnprocessable()->assertExactJson([
            'error' => 'No existe el o los roles que esta intentando asignar',
            'code' => 422,
        ]);
    }

    #[Test]
    public function returns_422_when_assigning_a_missing_permission(): void
    {
        Route::post('/api/test-errors/permission', function (Request $request): void {
            $request->user()->givePermissionTo('test-missing-permission');
        })->middleware('auth');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/test-errors/permission');

        $response->assertUnprocessable()->assertExactJson([
            'error' => 'No existe el o los permisos que esta intentando asignar',
            'code' => 422,
        ]);
    }

    #[Test]
    public function returns_429_with_retry_and_rate_limit_headers(): void
    {
        $this->freezeTime();
        RateLimiter::for('test-errors', fn (): Limit => Limit::perMinute(1)->by('test-errors'));
        Route::get('/api/test-errors/throttle', fn (): JsonResponse => response()->json([]))
            ->middleware('throttle:test-errors');
        $this->getJson('/api/test-errors/throttle')->assertOk();

        $response = $this->getJson('/api/test-errors/throttle');

        $response->assertTooManyRequests()->assertExactJson([
            'error' => 'Se ha revasado el numero de solicitudes',
            'code' => 429,
        ])->assertHeader('Retry-After', '60')
            ->assertHeader('X-RateLimit-Limit', '1')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertHeader('X-RateLimit-Reset');
    }

    #[Test]
    public function preserves_a_generic_http_status_message_and_headers(): void
    {
        Route::get('/api/test-errors/http', function (): void {
            abort(401, 'Autenticación requerida', ['WWW-Authenticate' => 'Bearer']);
        });

        $response = $this->getJson('/api/test-errors/http');

        $response->assertUnauthorized()->assertExactJson([
            'error' => 'Autenticación requerida',
            'code' => 401,
        ])->assertHeader('WWW-Authenticate', 'Bearer');
    }

    #[Test]
    public function returns_403_with_a_status_description_when_the_message_is_empty(): void
    {
        Route::get('/api/test-errors/forbidden', function (): void {
            abort(403);
        });

        $response = $this->getJson('/api/test-errors/forbidden');

        $response->assertForbidden()->assertExactJson(['error' => 'Forbidden', 'code' => 403]);
    }

    #[Test]
    public function returns_500_without_internal_details_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-errors/internal', function (): void {
            throw new RuntimeException('Private database credentials and query details');
        });

        $response = $this->getJson('/api/test-errors/internal');

        $response->assertInternalServerError()->assertExactJson([
            'error' => 'Error interno del servidor',
            'code' => 500,
        ]);
    }

    #[Test]
    public function preserves_debug_details_for_an_internal_failure(): void
    {
        config(['app.debug' => true]);
        Route::get('/api/test-errors/internal', function (): void {
            throw new RuntimeException('Internal failure for debugging');
        });

        $response = $this->getJson('/api/test-errors/internal');

        $response->assertInternalServerError()->assertExactJson([
            'message' => 'Excepción no controlada',
            'error' => 'Internal failure for debugging',
        ]);
    }

    #[Test]
    public function preserves_503_and_retry_header_without_exposing_internal_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-errors/unavailable', function (): void {
            abort(503, 'Private upstream host and credentials', ['Retry-After' => '120']);
        });

        $response = $this->getJson('/api/test-errors/unavailable');

        $response->assertServiceUnavailable()->assertExactJson([
            'error' => 'Error interno del servidor',
            'code' => 503,
        ])->assertHeader('Retry-After', '120');
    }

    private function registerPermissionProtectedRoute(): void
    {
        Route::get('/api/test-errors/protected', fn (): JsonResponse => response()->json(['message' => 'Permitido']))
            ->middleware(['auth', 'can:test.errors.view']);
    }
}
