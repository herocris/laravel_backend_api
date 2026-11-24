<?php

namespace App\Http\Requests\Api\V1\DrugPresentation;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\DrugPresentationIndexDefinition;

class IndexDrugPresentationRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return DrugPresentationIndexDefinition::definition();
    }
}
