<?php

namespace App\Http\Requests\Api\V1\Drug;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\DrugIndexDefinition;

class IndexDrugRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return DrugIndexDefinition::definition();
    }
}
