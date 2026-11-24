<?php

namespace App\Http\Requests\Api\V1\Role;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\RoleIndexDefinition;

class IndexRoleRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return RoleIndexDefinition::definition();
    }
}
