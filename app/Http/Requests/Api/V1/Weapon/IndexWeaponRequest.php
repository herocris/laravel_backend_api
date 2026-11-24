<?php

namespace App\Http\Requests\Api\V1\Weapon;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Support\Query\Definitions\WeaponIndexDefinition;

class IndexWeaponRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    protected function definition(): array
    {
        return WeaponIndexDefinition::definition();
    }
}
