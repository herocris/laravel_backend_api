<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class ErrorResponseTest extends UnitTestCase
{
    #[Test]
    public function it_returns_a_json_response_for_http_errors(): void
    {
        Route::get('/test/error', function (): void {
            abort(409, 'Conflict');
        });

        $response = $this->getJson('/test/error');

        $this->assertInstanceOf(JsonResponse::class, $response->baseResponse);
        $response->assertStatus(409)->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function it_returns_correct_http_status_code(): void
    {
        Route::get('/test/error', function (): void {
            abort(401, 'Unauthorized');
        });

        $response = $this->getJson('/test/error');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_correct_json_structure(): void
    {
        Route::get('/test/error', function (): void {
            abort(422, 'Invalid data');
        });

        $response = $this->getJson('/test/error');

        $response->assertUnprocessable()->assertExactJson(['error' => 'Invalid data', 'code' => 422]);
    }

    #[Test]
    public function it_returns_an_array_of_validation_messages(): void
    {
        Route::get('/test/error', function (): void {
            throw ValidationException::withMessages(['email' => 'Invalid format', 'password' => 'Too short']);
        });

        $response = $this->getJson('/test/error');

        $response->assertUnprocessable()->assertExactJson([
            'error' => ['email' => ['Invalid format'], 'password' => ['Too short']],
            'code' => 422,
        ]);
    }
}
