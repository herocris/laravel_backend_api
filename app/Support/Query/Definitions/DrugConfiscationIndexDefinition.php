<?php

namespace App\Support\Query\Definitions;

use App\Support\Query\IndexQueryApplier;
use Illuminate\Database\Eloquent\Builder;

class DrugConfiscationIndexDefinition
{
    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'fields' => [
                'identificador' => 'id',
                'cantidad' => 'amount',
                'peso' => 'weight',
                'foto' => 'photo',
            ],
            'with' => ['confiscation:id,observation', 'drug:id,description', 'drugPresentation:id,description'],
            'searchable' => [
                fn (Builder $query, string $value) => $query->whereHas('confiscation', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'observation', $value)),
                fn (Builder $query, string $value) => $query->whereHas('drugPresentation', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
                fn (Builder $query, string $value) => $query->whereHas('drug', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
            ],
            'includes' => [
                'decomiso' => 'confiscation:id,observation',
                'droga' => 'drug:id,description',
                'presentacion' => 'drugPresentation:id,description',
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
                'peso' => [
                    'rules' => ['sometimes', 'required', 'numeric'],
                    'query' => 'weight',
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
                'droga' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'drug_id',
                ],
                'droga_descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('drug', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
                ],
                'presentacion' => [
                    'rules' => ['sometimes', 'required', 'integer', 'min:1'],
                    'query' => 'drug_presentation_id',
                ],
                'presentacion_descripcion' => [
                    'rules' => ['sometimes', 'required', 'string', 'max:255'],
                    'query' => fn (Builder $query, string $value) => $query->whereHas('drugPresentation', fn (Builder $related) => IndexQueryApplier::whereContains($related, 'description', $value)),
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
