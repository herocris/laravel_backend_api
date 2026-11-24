<?php

namespace App\Support\Query\Definitions;

use Illuminate\Database\Eloquent\Builder;

class RoleIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'nombre' => 'name',
            ],
            'with' => ['permissions:id'],
            'searchable' => [
                'name',
            ],
            'includes' => [
                'permisos' => 'permissions:id',
            ],
            'filters' => [
                'identificador' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'id',
                ],
                'nombre' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'name',
                    'operator' => 'like',
                ],
                'permiso' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => fn (Builder $query, int|string $value) => $query->whereHas('permissions', fn (Builder $related) => $related->whereKey($value)),
                ],
            ],
        ];
    }
}
