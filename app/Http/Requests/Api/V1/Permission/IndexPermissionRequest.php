<?php

namespace App\Http\Requests\Api\V1\Permission;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\PermissionIndexDefinition;

class IndexPermissionRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return PermissionIndexDefinition::definition();
    }
}
