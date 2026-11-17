<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class PaginateTest extends UnitTestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_the_sql_page_and_pagination_metadata(): void
    {
        User::factory()->count(4)->create();
        $this->app->instance('request', Request::create('/test', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());
        $page = $response->getData(true);

        $this->assertSame([1, 2, 3, 4], array_column($page['data'], 'identificador'));
        $this->assertSame(4, $page['total']);
        $this->assertSame(10, $page['per_page']);
        $this->assertArrayHasKey('links', $page);
    }

    #[Test]
    public function returns_the_requested_sql_page(): void
    {
        User::factory()->count(4)->create();
        $this->app->instance('request', Request::create('/test?page=2&per_page=2', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());
        $page = $response->getData(true);

        $this->assertSame(2, $page['current_page']);
        $this->assertSame([3, 4], array_column($page['data'], 'identificador'));
    }

    #[Test]
    public function limits_the_sql_page_size(): void
    {
        User::factory()->count(4)->create();
        $this->app->instance('request', Request::create('/test?per_page=2', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());
        $page = $response->getData(true);

        $this->assertSame(2, $page['per_page']);
        $this->assertSame([1, 2], array_column($page['data'], 'identificador'));
        $this->assertSame(4, $page['total']);
    }
}
