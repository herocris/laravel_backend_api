<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class SortDataTest extends UnitTestCase
{
    use RefreshDatabase;

    #[Test]
    public function sorts_the_sql_listing_ascending(): void
    {
        User::factory()->count(4)->create();
        $this->app->instance('request', Request::create('/test?sort_by=identificador&type=asc', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([1, 2, 3, 4], array_column($response->getData(true)['data'], 'identificador'));
    }

    #[Test]
    public function sorts_the_sql_listing_descending(): void
    {
        User::factory()->count(4)->create();
        $this->app->instance('request', Request::create('/test?sort_by=identificador&type=desc', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([4, 3, 2, 1], array_column($response->getData(true)['data'], 'identificador'));
    }
}
