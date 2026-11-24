<?php

namespace App\Support\Query\Definitions;

class WeaponIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'descripcion' => 'description',
                'logo' => 'logo',
            ],
            'with' => [],
            'searchable' => [
                'description',
            ],
            'includes' => [
            ],
            'filters' => [
                'identificador' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'id',
                ],
                'descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'description',
                    'operator' => 'like',
                ],
                'logo' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'logo',
                    'operator' => 'like',
                ],
            ],
        ];
    }
}
