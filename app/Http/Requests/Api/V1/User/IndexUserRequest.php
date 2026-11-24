<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\UserIndexDefinition;

class IndexUserRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return UserIndexDefinition::definition();
    }
}
