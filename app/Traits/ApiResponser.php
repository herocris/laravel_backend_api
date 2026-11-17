<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Trait ApiResponser
 *
 * Proporciona métodos reutilizables para formatear respuestas de API REST, incluyendo:
 * - Respuestas de éxito estandarizadas
 * - Transformación de datos mediante Resources
 * - Filtrado, ordenamiento y paginación SQL de listados
 *
 * Uso: incluir este trait en controladores de API para estandarizar respuestas.
 */
trait ApiResponser
{
    /**
     * Genera una respuesta JSON de éxito.
     *
     * @param  mixed  $data  Datos a incluir en la respuesta
     * @param  int  $code  Código de estado HTTP (por defecto 200)
     */
    private function successResponse(mixed $data, int $code): JsonResponse
    {
        return response()->json($data, $code);
    }

    protected function showOne(JsonResource $resource, int $code = 200): JsonResponse
    {
        return $this->successResponse($resource, $code);
    }

    /**
     * Filtra y pagina en SQL antes de transformar únicamente la página solicitada.
     *
     * @param  class-string<JsonResource>  $resource  Resource elegido por el controlador
     * @param  array<string, string>  $fields  Campos públicos escalares y columnas permitidas
     */
    protected function showAll(Builder $query, string $resource, array $fields, int $code = 200): JsonResponse
    {
        $rules = [
            'per_page' => ['sometimes', 'required', 'integer', 'min:2', 'max:100'],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'required', 'string', Rule::in(array_keys($fields))],
            'type' => ['sometimes', 'required', 'string', Rule::in(['asc', 'desc'])],
        ];

        foreach (array_keys($fields) as $field) {
            $rules[$field] = ['sometimes', 'nullable', 'string'];
        }

        $parameters = Validator::validate(request()->query(), $rules);
        $grammar = $query->getQuery()->getGrammar();

        foreach ($fields as $field => $column) {
            if (isset($parameters[$field])) {
                $qualifiedColumn = $grammar->wrap($query->qualifyColumn($column));
                $value = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($parameters[$field]));
                $query->whereRaw("LOWER({$qualifiedColumn}) LIKE ? ESCAPE '!'", ['%'.$value.'%']);
            }
        }

        if (isset($parameters['sort_by'])) {
            $query->orderBy($query->qualifyColumn($fields[$parameters['sort_by']]), $parameters['type'] ?? 'desc');
        }

        $query->orderBy($query->getModel()->getQualifiedKeyName());
        $paginated = $query->paginate((int) ($parameters['per_page'] ?? 10), ['*'], 'page', (int) ($parameters['page'] ?? 1));
        $paginated->appends(request()->query());
        $paginated->setCollection($this->transformData($paginated->getCollection(), $resource));

        return $this->successResponse($paginated, $code);
    }

    /** @param class-string<JsonResource> $resource */
    protected function transformData(Collection $collection, string $resource): Collection
    {
        return collect($resource::collection($collection)->toArray(request()));
    }
}
