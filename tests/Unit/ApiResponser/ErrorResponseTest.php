<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Tests\ApiResponserProxy;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class ErrorResponseTest extends UnitTestCase
{
    #[Test]
    public function it_returns_a_json_response_instance(): void
    {
        $proxy = new ApiResponserProxy();
        $response = $proxy->callError('Not found', 404);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function it_returns_correct_http_status_code(): void
    {
        $proxy = new ApiResponserProxy();
        $response = $proxy->callError('Unauthorized', 401);

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function it_returns_correct_json_structure(): void
    {
        $proxy = new ApiResponserProxy();
        $response = $proxy->callError('Invalid data', 422);

        $expected = [
            'error' => 'Invalid data',
            'code' => 422,
        ];

        $this->assertEquals($expected, $response->getData(true));
    }

    #[Test]
    public function it_accepts_array_as_error_message(): void
    {
        $message = ['email' => 'Invalid format', 'password' => 'Too short'];

        $proxy = new ApiResponserProxy();
        $response = $proxy->callError($message, 422);

        $this->assertEquals([
            'error' => $message,
            'code' => 422,
        ], $response->getData(true));
    }
}
