<?php

namespace App\Support\Query\Definitions;

use Illuminate\Database\Eloquent\Builder;

class ActivityLogIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'tipo_de_evento' => 'event',
                'descripcion' => 'description',
                'id_usuario' => 'causer_id',
                'usuario' => 'log_name',
                'fecha' => 'created_at',
            ],
            'with' => [],
            'searchable' => [
                'event',
                'description',
                'log_name',
            ],
            'selectable' => [
                'identificador' => 'id',
                'tipo_de_evento' => 'event',
                'descripcion' => 'description',
                'id_usuario' => 'causer_id',
                'usuario' => 'log_name',
                'fecha' => 'created_at',
                'cambios' => 'properties',
            ],
            'includes' => [
            ],
            'filters' => [
                'identificador' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'id',
                ],
                'tipo_de_evento' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'event',
                    'operator' => 'like',
                ],
                'descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'description',
                    'operator' => 'like',
                ],
                'id_usuario' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'causer_id',
                ],
                'usuario' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'log_name',
                    'operator' => 'like',
                ],
                'fecha' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('created_at'), $value),
                ],
                'fecha_desde' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('created_at'), '>=', $value),
                ],
                'fecha_hasta' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereDate($query->qualifyColumn('created_at'), '<=', $value),
                ],
            ],
        ];
    }
}
