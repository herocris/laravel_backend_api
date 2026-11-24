<?php

namespace App\Http\Requests\Api\V1\AmmunitionConfiscation;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\AmmunitionConfiscationIndexDefinition;

class IndexAmmunitionConfiscationRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return AmmunitionConfiscationIndexDefinition::definition();
    }
}
