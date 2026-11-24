<?php

namespace App\Support\Query\Definitions;

use App\Support\Query\IndexQueryApplier;
use Illuminate\Database\Eloquent\Builder;

class AmmunitionConfiscationIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'cantidad' => 'amount',
                'foto' => 'photo',
            ],
            'with' => ['confiscation:id,observation', 'ammunition:id,description'],
            'searchable' => [
                fn (Builder $query, string $value) => $query->whereHas('ammunition', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
                fn (Builder $query, string $value) => $query->whereHas('confiscation', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'observation', $value)),
            ],
            'includes' => [
                'decomiso' => 'confiscation:id,observation',
                'municion' => 'ammunition:id,description',
            ],
            'filters' => [
                'identificador' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'id',
                ],
                'cantidad' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'amount',
                ],
                'foto' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => 'photo',
                    'operator' => 'like',
                ],
                'decomiso' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'confiscation_id',
                ],
                'decomiso_descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('confiscation', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'observation', $value)),
                ],
                'municion' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'ammunition_id',
                ],
                'municion_descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('ammunition', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
                ],
                'fecha_desde' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('confiscation', fn (Builder $related) => $related->whereDate($related->qualifyColumn('date'), '>=', $value)),
                ],
                'fecha_hasta' => [
                    'rules' => ['sometimes', 'required', 'date_format:Y-m-d'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('confiscation', fn (Builder $related) => $related->whereDate($related->qualifyColumn('date'), '<=', $value)),
                ],
            ],
        ];
    }
}
