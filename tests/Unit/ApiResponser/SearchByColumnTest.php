<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class SearchByColumnTest extends UnitTestCase
{
    use RefreshDatabase;

    #[Test]
    public function searches_the_sql_listing_by_identifier(): void
    {
        $this->createUsers();
        $this->app->instance('request', Request::create('/test?identificador=1', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([['identificador' => 1, 'nombre' => 'Uno']], $response->getData(true)['data']);
    }

    #[Test]
    public function returns_the_sql_listing_without_filters(): void
    {
        $this->createUsers();
        $this->app->instance('request', Request::create('/test', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([1, 2, 3, 4], array_column($response->getData(true)['data'], 'identificador'));
    }

    #[Test]
    public function search_is_case_insensitive_and_partial(): void
    {
        $this->createUsers();
        $this->app->instance('request', Request::create('/test?nombre=RE', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([['identificador' => 3, 'nombre' => 'Tres']], $response->getData(true)['data']);
    }

    #[Test]
    public function search_by_non_existing_column_is_ignored(): void
    {
        $this->createUsers();
        $this->app->instance('request', Request::create('/test?fake=123', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([1, 2, 3, 4], array_column($response->getData(true)['data'], 'identificador'));
    }

    #[Test]
    public function search_by_multiple_columns_filters_combined(): void
    {
        $this->createUsers();
        $this->app->instance('request', Request::create('/test?nombre=tr&identificador=3', 'GET'));

        $response = (new ApiResponserProxy)->callShowAll(User::query());

        $this->assertSame([['identificador' => 3, 'nombre' => 'Tres']], $response->getData(true)['data']);
    }

    private function createUsers(): void
    {
        User::factory()->count(4)->sequence(
            ['name' => 'Uno'],
            ['name' => 'Dos'],
            ['name' => 'Tres'],
            ['name' => 'Cuatro'],
        )->create();
    }
}
