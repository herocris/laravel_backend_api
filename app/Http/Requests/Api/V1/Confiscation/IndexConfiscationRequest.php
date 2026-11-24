<?php

namespace App\Http\Requests\Api\V1\Confiscation;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\ConfiscationIndexDefinition;

class IndexConfiscationRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return ConfiscationIndexDefinition::definition();
    }
}
