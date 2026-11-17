<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class showAllTest extends UnitTestCase
{
    use RefreshDatabase;

    #[Test]
    public function return_show_all_if_collection_not_empty(): void
    {
        User::factory()->count(4)->sequence(
            ['name' => 'Uno'],
            ['name' => 'Dos'],
            ['name' => 'Cuatro'],
            ['name' => 'Tres'],
        )->create();
        $proxy = new ApiResponserProxy;

        $response = $proxy->callShowAll(User::query());
        $data = $response->getData(); // true = array asociativo

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($data->data);
        $this->assertCount(4, $data->data);
    }

    #[Test]
    public function return_show_all_if_collection_is_empty()
    {
        $proxy = new ApiResponserProxy;

        $response = $proxy->callShowAll(User::query());
        $data = $response->getData();
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($data->data);
        $this->assertCount(0, $data->data);
    }
}
