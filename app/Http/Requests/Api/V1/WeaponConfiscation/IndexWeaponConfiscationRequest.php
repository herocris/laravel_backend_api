<?php

namespace App\Http\Requests\Api\V1\WeaponConfiscation;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\WeaponConfiscationIndexDefinition;

class IndexWeaponConfiscationRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return WeaponConfiscationIndexDefinition::definition();
    }
}
