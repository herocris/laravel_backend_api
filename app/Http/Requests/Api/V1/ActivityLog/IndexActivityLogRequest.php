<?php

namespace App\Http\Requests\Api\V1\ActivityLog;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\ActivityLogIndexDefinition;

class IndexActivityLogRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return ActivityLogIndexDefinition::definition();
    }
}
