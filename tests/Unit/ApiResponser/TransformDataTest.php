<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\FakeModel;
use Tests\UnitTestCase;

class TransformDataTest extends UnitTestCase
{
    #[Test]
    public function transforms_collection_using_resource_class(): void
    {
        $items = collect([
            new FakeModel(1, 'Uno'),
            new FakeModel(2, 'Dos'),
        ]);

        $proxy = new ApiResponserProxy();

        $transformed = $proxy->callTransformData($items);
        // Debe regresar una Collection con la estructura transformada
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transformed);

        $this->assertSame([
            ['identificador' => 1, 'nombre' => 'Uno'],
            ['identificador' => 2, 'nombre' => 'Dos'],
        ], $transformed->toArray());
    }

}
