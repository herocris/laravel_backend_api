<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class PaginateTest extends UnitTestCase
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
    public function correct_structure_collection_paginate(): void
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        $proxy = new ApiResponserProxy();

        $paginated = $proxy->callPaginate($this->items);
        // Debe regresar una Collection con la estructura ordenada
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
    }
    #[Test]
    public function correct_page_result(): void
    {
        $this->app->instance('request', Request::create('/test?page=2', 'GET'));

        $proxy = new ApiResponserProxy();

        $paginated = $proxy->callPaginate($this->items);
        $this->assertEquals(2, $paginated->currentPage());
    }
    #[Test]
    public function correct_per_page_result(): void
    {
        $this->app->instance('request', Request::create('/test?per_page=2', 'GET'));

        $proxy = new ApiResponserProxy();
        $paginated = $proxy->callPaginate($this->items);

        $this->assertEquals(2, $paginated->perPage());
        $this->assertCount(2, $paginated->items());
    }
}
