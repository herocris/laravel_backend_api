<?php

namespace App\Http\Requests\Api\V1\Ammunition;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\AmmunitionIndexDefinition;

class IndexAmmunitionRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return AmmunitionIndexDefinition::definition();
    }
}
