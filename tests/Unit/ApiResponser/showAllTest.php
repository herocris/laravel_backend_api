<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\FakeModel;
use Tests\UnitTestCase;
use Illuminate\Http\JsonResponse;

class showAllTest extends UnitTestCase
{
    #[Test]
    public function return_show_all_if_collection_not_empty(): void
    {
        $items = collect([
            new FakeModel(1, 'Uno'),
            new FakeModel(2, 'Dos'),
            new FakeModel(4, 'Cuatro'),
            new FakeModel(3, 'Tres'),
        ]);
        $proxy = new ApiResponserProxy();

        $response = $proxy->callShowAll($items);
        $data = $response->getData(); // true = array asociativo
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($data->data);
        $this->assertCount(4, $data->data);
    }
    #[Test]
    public function return_show_all_if_collection_is_empty()
    {
        $items = collect([]);
        $proxy = new ApiResponserProxy();

        $response = $proxy->callShowAll($items);
        $data = $response->getData();
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($data->data);
        $this->assertCount(0, $data->data);
    }
}
