<?php

namespace App\Support\Query\Definitions;

class PermissionIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'nombre' => 'name',
            ],
            'with' => [],
            'searchable' => [
                'name',
            ],
            'includes' => [
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
            ],
        ];
    }
}
