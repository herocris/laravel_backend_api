<?php

namespace App\Http\Requests\Api\V1\DrugConfiscation;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\DrugConfiscationIndexDefinition;

class IndexDrugConfiscationRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return DrugConfiscationIndexDefinition::definition();
    }
}
