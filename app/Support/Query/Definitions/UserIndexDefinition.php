<?php

namespace App\Support\Query\Definitions;

use Illuminate\Database\Eloquent\Builder;

class UserIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'nombre' => 'name',
                'correo' => 'email',
            ],
            'with' => ['roles:id', 'permissions:id'],
            'searchable' => [
                'name',
                'email',
            ],
            'includes' => [
                'permisos' => 'permissions:id',
                'roles' => 'roles:id',
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
                'correo' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'email',
                    'operator' => 'like',
                ],
                'permiso' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => fn (Builder $query, int|string $value) => $query->whereHas('permissions', fn (Builder $related) => $related->whereKey($value)),
                ],
                'rol' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => fn (Builder $query, int|string $value) => $query->whereHas('roles', fn (Builder $related) => $related->whereKey($value)),
                ],
            ],
        ];
    }
}
