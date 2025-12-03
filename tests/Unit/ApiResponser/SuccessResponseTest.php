<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class SuccessResponseTest extends UnitTestCase
{
    #[Test]
    public function it_returns_a_json_response_instance(): void
    {
        $proxy = new ApiResponserProxy();

        $response = $proxy->callSuccess(['message' => 'ok'], 200);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function it_returns_correct_http_status_code(): void
    {
        $proxy = new ApiResponserProxy();

        $response = $proxy->callSuccess(['message' => 'created'], 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function it_returns_exact_json_payload(): void
    {
        $proxy = new ApiResponserProxy();

        $payload = ['message' => 'success'];

        $response = $proxy->callSuccess($payload, 200);

        $this->assertEquals($payload, $response->getData(true));
    }
}
