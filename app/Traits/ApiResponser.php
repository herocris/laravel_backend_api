<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Trait ApiResponser
 *
 * Proporciona métodos reutilizables para formatear respuestas de API REST, incluyendo:
 * - Respuestas de éxito y error estandarizadas
 * - Transformación de datos mediante Resources
 * - Paginación, ordenamiento, búsqueda y filtrado de colecciones
 * - Caché de respuestas basado en URL y parámetros
 * - Formateo específico para gráficas de barras, líneas y pastel
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
     * @return JsonResponse
     */
    private function successResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    /**
     * Genera una respuesta JSON de error estandarizada.
     *
     * @param  string  $message  Mensaje descriptivo del error
     * @param  int  $code  Código de estado HTTP (400, 404, 500, etc.)
     * @return JsonResponse
     */
    protected function errorResponse($message, $code)
    {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }

    /**
     * Formatea resultados para gráfica de líneas/barras agrupando por período.
     *
     * A partir de una colección de objetos con al menos las claves
     * 'period', 'total_amount' y la descripción dinámica del criterio
     * (por ejemplo, 'drug_description', 'weapon_description', etc.),
     * agrupa por período y construye entradas con el nombre del período
     * y pares clave-valor donde la clave es la descripción y el valor
     * es el total del período.
     *
     * Ejemplo de salida:
     * [
     *   { name: '2025-11', 'Cocaína': 10, 'Marihuana': 5 },
     *   { name: '2025-12', 'Cocaína': 3 }
     * ]
     *
     * @param  Collection  $queryResult  Colección de filas ya agregadas con 'period' y 'total_amount'.
     * @param  string  $criteria  Tipo de catálogo para resolver la descripción (drugs|presentations|weapons|ammunitions).
     * @return Collection Colección indexada con entradas por período lista para consumo en gráficas.
     */
    protected function FormatCollectionBarLine(Collection $queryResult, $criteria)
    {
        $linedata = collect($queryResult) // formateando data para grafica de linea
            ->groupBy('period')
            ->map(function ($group, $period) use ($criteria) {
                $entry = ['name' => $period];
                foreach ($group as $item) {
                    /** @var object $item */
                    $entry[$item->{$this->ConfiscationDescription($criteria)}] = $item->total_amount; // accediendo de manera dinamica a las propiedades de un item
                }

                return $entry;
            })
            ->values(); // para tener índices numéricos (array plano)

        return $linedata;
    }

    /**
     * Formatea resultados para gráfica de pastel agrupando por descripción.
     *
     * Agrupa la colección por la descripción dinámica del criterio
     * (por ejemplo, 'drug_description', 'weapon_description', etc.) y
     * suma el campo 'total_amount' para cada grupo, retornando una
     * colección de pares nombre/valor.
     *
     * Ejemplo de salida:
     * [
     *   { name: 'Cocaína', value: 13 },
     *   { name: 'Marihuana', value: 5 }
     * ]
     *
     * @param  Collection  $queryResult  Colección de filas con la descripción y 'total_amount'.
     * @param  string  $criteria  Tipo de catálogo (drugs|presentations|weapons|ammunitions).
     * @return Collection Colección de objetos con 'name' y 'value' lista para gráficas de pastel.
     */
    protected function FormatCollectionPie(Collection $queryResult, $criteria)
    {
        $pieData = collect($queryResult) // formateando data para grafica de pastel
            ->groupBy($this->ConfiscationDescription($criteria))
            ->map(function ($group, $name) {
                return [
                    'name' => $name,
                    'value' => $group->sum('total_amount'),
                ];
            })
            ->values(); // para tener índices numéricos (array plano)

        return $pieData;
    }

    /**
     * Retorna el nombre del campo descriptivo según el tipo de catálogo.
     *
     * Este método se usa para resolver dinámicamente el atributo
     * descriptivo en los resultados agregados (por ejemplo, para drogas
     * retorna 'drug_description').
     *
     * @param  string  $typeConfiscation  Tipo de catálogo: 'drugs', 'presentations', 'weapons', 'ammunitions'.
     * @return string Nombre del atributo descriptivo correspondiente.
     */
    protected function ConfiscationDescription($typeConfiscation)
    {
        return match ($typeConfiscation) {
            'drugs' => 'drug_description',
            'presentations' => 'drug_presentation_description',
            'weapons' => 'weapon_description',
            'ammunitions' => 'ammunition_description',
        };
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

        return $this->successResponse($this->cacheResponse($paginated), $code);
    }

    protected function sortData(Collection $collection)
    {
        if (request()->has('sort_by')) {
            if (request()->type == 'asc') {
                $collection = $collection->sortBy(request()->sort_by);
            } else {
                $collection = $collection->sortByDesc(request()->sort_by);
            }
        }

        return $collection->values();
    }

    protected function searchByColumn(Collection $collection)
    {
        foreach (request()->query() as $field => $value) { // request()->query() obtiene un arreglo de parametros de la url de la columna y el valor a buscar
            if (in_array($field, array_keys($collection->first()))) { // Lista de filtros permitidos obtenidos directatmente del primero elemento de la colección
                if (isset($field, $value)) { // Si el campo y el valor están definidos
                    $collection = $collection->filter(function ($item) use ($field, $value) {
                        return Str::contains(strtolower($item[$field]), strtolower($value));
                    });
                }
            }
        }

        return $collection->values();
    }

    /** @param class-string<JsonResource> $resource */
    protected function transformData(Collection $collection, string $resource): Collection
    {
        return collect($resource::collection($collection)->toArray(request()));
    }

    protected function paginate(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:100',
        ];

        Validator::validate(request()->all(), $rules);

        $page = LengthAwarePaginator::resolveCurrentPage();

        $perPage = 10;
        if (request()->has('per_page')) {
            $perPage = (int) request()->per_page;
        }

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        $paginated->appends(request()->all());

        return $paginated;
    }

    protected function cacheResponse($data)
    {
        // se obtienen la URL sin parámetros, por ejemplo: http://localhost/test
        $url = request()->url();
        // se obtienen los parámetros de la URL en forma de arreglo asociativo
        // ejemplo: ['sort_by' => 'name', 'type' => 'asc']
        $queryParams = request()->query();
        // se ordenan las claves alfabéticamente para generar una clave consistente
        // ejemplo: ['sort_by' => 'name', 'type' => 'asc']
        // en lugar de: ['type' => 'asc', 'sort_by' => 'name']
        ksort($queryParams);
        // se construye la cadena de consulta a partir del arreglo ordenado
        // ejemplo: sort_by=name&type=asc
        $queryString = http_build_query($queryParams);
        // se combina la URL base con la cadena de consulta para formar la clave completa
        // ejemplo: http://localhost/test?sort_by=name&type=asc
        $fullUrl = "{$url}?{$queryString}";

        // se utiliza la fachada Cache para almacenar y recuperar la respuesta en caché
        return Cache::remember($fullUrl, 30 / 60, function () use ($data) {
            return $data;
        });
    }
}
