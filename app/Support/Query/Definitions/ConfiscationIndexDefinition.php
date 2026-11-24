<?php

namespace App\Support\Query\Definitions;

use Illuminate\Database\Eloquent\Builder;

class ConfiscationIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'fecha' => 'date',
                'observacion' => 'observation',
                'direccion' => 'direction',
                'departamento' => 'department',
                'municipalidad' => 'municipality',
                'latitud' => 'latitude',
                'longitud' => 'length',
            ],
            'with' => [],
            'searchable' => [
                'observation',
                'direction',
                'department',
                'municipality',
            ],
            'includes' => [
            ],
            'filters' => [
                'identificador' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'id',
                ],
                'fecha' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('date'), $value),
                ],
                'observacion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'observation',
                    'operator' => 'like',
                ],
                'direccion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'direction',
                    'operator' => 'like',
                ],
                'departamento' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'department',
                    'operator' => 'like',
                ],
                'municipalidad' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'municipality',
                    'operator' => 'like',
                ],
                'latitud' => [
                    'rules' => ['sometimes', 'required', 'numeric'],
                    'query' => 'latitude',
                ],
                'longitud' => [
                    'rules' => ['sometimes', 'required', 'numeric'],
                    'query' => 'length',
                ],
                'fecha_desde' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('date'), '>=', $value),
                ],
                'fecha_hasta' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('date'), '<=', $value),
                ],
            ],
        ];
    }
}
