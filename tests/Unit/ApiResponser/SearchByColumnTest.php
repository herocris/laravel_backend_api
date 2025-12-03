<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class SearchByColumnTest extends UnitTestCase
{
    private Collection $items;
    protected function setUp(): void
    {
        parent::setUp();
        $this->items = collect([
            ['identificador' => 2, 'nombre' => 'Dos'],
            ['identificador' => 1, 'nombre' => 'Uno'],
            ['identificador' => 4, 'nombre' => 'Cuatro'],
            ['identificador' => 3, 'nombre' => 'Tres'],
        ]);
    }
    #[Test]
    public function search_by_identificador(): void
    {
        $this->app->instance('request', Request::create('/test?identificador=1', 'GET'));

        $proxy = new ApiResponserProxy();

        $sorted = $proxy->callSearchByColumn($this->items);
        // Debe regresar una Collection con la estructura ordenada
        $this->assertInstanceOf(Collection::class, $sorted);

        $this->assertSame([
            ['identificador' => 1, 'nombre' => 'Uno'],
        ], $sorted->toArray());
    }
    #[Test]
    public function search_by_empty_parameters(): void
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        $proxy = new ApiResponserProxy();

        $sorted = $proxy->callSearchByColumn($this->items);
        // Debe regresar una Collection con la estructura ordenada
        $this->assertInstanceOf(Collection::class, $sorted);

        $this->assertSame($this->items->toArray(), $sorted->toArray());
    }
    #[Test]
    public function search_is_case_insensitive_and_partial(): void
    {
        $this->app->instance('request', Request::create('/test?nombre=TRES', 'GET'));

        $proxy = new ApiResponserProxy();

        $result = $proxy->callSearchByColumn($this->items);

        $this->assertSame([
            ['identificador' => 3, 'nombre' => 'Tres'],
        ], $result->toArray());
    }
    #[Test]
    public function search_by_non_existing_column_is_ignored(): void
    {
        $this->app->instance('request', Request::create('/test?fake=123', 'GET'));

        $proxy = new ApiResponserProxy();

        $result = $proxy->callSearchByColumn($this->items);

        $this->assertSame($this->items->values()->toArray(), $result->toArray());
    }
    #[Test]
    public function search_by_multiple_columns_filters_combined(): void
    {
        $this->app->instance(
            'request',
            Request::create('/test?nombre=tr&identificador=3', 'GET')
        );

        $proxy = new ApiResponserProxy();

        $result = $proxy->callSearchByColumn($this->items);

        $this->assertSame([
            ['identificador' => 3, 'nombre' => 'Tres'],
        ], $result->toArray());
    }
}
