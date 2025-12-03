<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class SortDataTest extends UnitTestCase
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
    public function sort_collection_asc(): void
    {
        $this->app->instance('request', Request::create('/test?sort_by=identificador&type=asc', 'GET'));

        $proxy = new ApiResponserProxy();

        $sorted = $proxy->callSortData($this->items);
        // Debe regresar una Collection con la estructura ordenada
        $this->assertInstanceOf(Collection::class, $sorted);

        $this->assertSame([
            ['identificador' => 1, 'nombre' => 'Uno'],
            ['identificador' => 2, 'nombre' => 'Dos'],
            ['identificador' => 3, 'nombre' => 'Tres'],
            ['identificador' => 4, 'nombre' => 'Cuatro'],
        ], $sorted->toArray());
    }
    #[Test]
    public function sort_collection_desc(): void
    {

        $this->app->instance('request', Request::create('/test?sort_by=identificador&type=desc', 'GET'));

        $proxy = new ApiResponserProxy();

        $sorted = $proxy->callSortData($this->items);
        // Debe regresar una Collection con la estructura ordenada
        $this->assertInstanceOf(Collection::class, $sorted);

        $this->assertSame([
            ['identificador' => 4, 'nombre' => 'Cuatro'],
            ['identificador' => 3, 'nombre' => 'Tres'],
            ['identificador' => 2, 'nombre' => 'Dos'],
            ['identificador' => 1, 'nombre' => 'Uno'],
        ], $sorted->toArray());
    }
}
