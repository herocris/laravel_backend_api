<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\FakeModel;
use Tests\UnitTestCase;
use Illuminate\Http\JsonResponse;
use Tests\FakeResource;

class showOneTest extends UnitTestCase
{
    #[Test]
    public function return_show_one(): void
    {
        $entity = new FakeModel(1, 'Uno');

        $proxy = new ApiResponserProxy();

        $response = $proxy->callShowOne($entity);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $data = $response->getData(true); // true = array asociativo

        // Verificamos que retorna la estructura del FakeResource
        $this->assertIsArray($data);
        $this->assertEquals(1, $data['identificador']);
        $this->assertEquals('Uno', $data['nombre']);
    }
    #[Test]
    public function test_show_one_uses_custom_status_code()
    {
        $proxy = new ApiResponserProxy();
        $fakeModel = new FakeModel(1, 'Uno');

        $response = $proxy->callShowOne($fakeModel, 201);

        $this->assertEquals(201, $response->status());
    }
}
