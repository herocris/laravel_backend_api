<?php

namespace App\Traits;

use App\Http\Requests\DrugConfiscation\GetRequest;
use App\Models\Drug;
use App\Models\DrugPresentation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait ApiResponser
{
    private function successResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    protected function errorResponse($message, $code)
    {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }

    protected function queryPeriod($period)
    {
        $validPeriods = [
            'day' => "DATE(full_date)",
            'month' => "DATE_FORMAT(full_date, '%Y-%m')",
            'quarter' => "CONCAT(YEAR(full_date), '-Q', QUARTER(full_date))",
            'semester' => "CONCAT(YEAR(full_date), '-S', IF(MONTH(full_date) <= 6, 1, 2))",
            'year' => "YEAR(full_date)",
            'total' => "DATE(full_date)",
        ];

        return $validPeriods[$period];
    }

    protected function queryFormater(Collection $queryResult, GetRequest $request)
    {
        $drugs = json_decode($request->input('drugs') ?? '[]');
        $presentations =json_decode($request->input('presentations') ?? '[]');
        $periodType = request()->input('period');
        $criteria=request()->input('criteria');
        $magnitude=request()->input('magnitude');
        // 🟢 Generar los períodos de tiempo dinámicamente
        $periodos = collect();
        $currentDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // Ajuste inicial según el tipo de periodo

        if ($periodType !== 'total') {
            switch ($periodType) {
                case 'day':
                    $format = 'Y-m-d';
                    break;
                case 'month':
                    $format = 'Y-m';
                    $currentDate->startOfMonth();
                    $endDate->endOfMonth();
                    break;
                case 'quarter':
                    $format = 'Y-\QQ';
                    $currentDate->startOfQuarter();
                    $endDate->endOfQuarter();
                    break;
                case 'semester':
                    $format = 'Y-\SS';  // Output: 2023-S1, 2023-S2
                    $currentDate->startOfQuarter(); // Laravel no tiene startOfSemester
                    $endDate->endOfQuarter();
                    break;
                case 'year':
                    $format = 'Y';
                    $currentDate->startOfYear();
                    $endDate->endOfYear();
                    break;
            }

            while ($currentDate <= $endDate) {
                $periodos->push($currentDate->format($format));
                match ($periodType) {
                    'day' => $currentDate->addDay(),
                    'month' => $currentDate->addMonth(),
                    'quarter' => $currentDate->addMonths(3),  // Trimestres avanzan 3 meses
                    'semester' => $currentDate->addMonths(6), // Semestres avanzan 6 meses
                    'year' => $currentDate->addYear(),
                };
            }
        } else {
            // Si el periodo es "total", agregamos un solo valor en "periodos"
            $periodos->push("$currentDate a $endDate");
        }

        $criterias = $criteria == 'drugs' ? $drugs : $presentations;
        // 🟢 Agrupar por drug_id o por drug_presentation_id segun $criteria y asegurar que solo estén los `drug_id` o `drug_presentation_id` pasados en la consulta

        $grouped = collect($criterias)->mapWithKeys(function ($criteria_id) use ($queryResult, $periodos, $periodType,$criteria,$magnitude) {
            $filtered = $queryResult->where($criteria == 'drugs' ? 'drug_id' : 'drug_presentation_id', $criteria_id);

            if ($periodType === 'total') {
                // Si el periodo es "total", sumamos todas las cantidades o pesos
                $totalValue = $magnitude == 'amount'
                    ? $filtered->sum('total_amount')
                    : $filtered->sum('total_weight');

                $data = [$totalValue]; // Solo un valor
            } else {
                // Si es otro periodo, asignar los valores normales
                $data = $magnitude == 'amount'
                    ? $periodos->map(fn($period) => optional($filtered->firstWhere('period', $period))->total_amount ?? 0)->toArray()
                    : $periodos->map(fn($period) => optional($filtered->firstWhere('period', $period))->total_weight ?? 0)->toArray();
            }

            $criteriaName = request()->input('criteria') == 'drugs' ? Drug::find($criteria_id)->description : DrugPresentation::find($criteria_id)->description;

            return [$criteria_id => [
                "atributo cualquiera 1" => "contenido de atributo1",
                "label" => $criteriaName,
                "data" => $data
            ]];
        });

        // 🟢 Formatear la respuesta final
        $response = [
            "datasets" => $grouped->values()->toArray(), // Convertir a array y asegurar que solo están los drug_id pasados
            "periodos" => $periodos->toArray()
        ];

        // 🟢 Devolver JSON
        return $response;
    }

    protected function showOne(Model $instance, $code = 200)
    {
        $resource = $instance->resource;
        $instance = new $resource($instance);
        return $this->successResponse($instance, $code);
    }
    protected function showAll(Collection $collection, $code = 200)
    {
        if ($collection->isEmpty()) {
            return $this->successResponse(['data' => $collection], $code);
        }

        $collection = $this->transformData($collection);
        $collection = $this->sortData($collection);
        $collection = $this->searchByColumn($collection);
        //$collection = $this->filterData($collection);
        $collection = $this->paginate($collection);
        $collection = $this->cacheResponse($collection);

        return $this->successResponse($collection, $code);
    }





    protected function filterData(Collection $collection)
    {
        //dd($collection);
        // foreach (request()->query() as $query => $value) {
        //     //$attribute = $transformer::originalAttribute($query);

        //     if (isset($query, $value)) {
        //         //dump($query);
        //         if (count($collection->where($query, $value))) {
        //             $collection = $collection->where($query, $value);
        //         }
        //         //$collection = $collection->where($query, $value);
        //     }
        // }

        // return $collection;
        $request = request();

        return $collection->filter(function ($item) use ($request) {
            $passes = true;
            foreach ($item->getRelations() as $query => $relation) {
                if ($request->filled('filters.' . $query)) {
                    if (!$relation instanceof \Illuminate\Database\Eloquent\Collection) { // cuando la relación es de uno a muchos
                        foreach (array_keys($item->$query->toArray()) as $query2 => $ind) {
                            if ($ind != 'id') {
                                $passes = $passes && in_array($item->$query->$ind, $request->filters[$query]);
                            }
                        }
                    }
                    if ($relation instanceof \Illuminate\Database\Eloquent\Collection) { // cuando la relación es de muchos a muchos
                        $passes = $passes && $item->$query->contains(function ($item) use ($request, $query) {
                            foreach (array_keys($item->toArray()) as $rtr => $ind) {
                                if ($ind != 'id' && $ind != 'pivot') {
                                    return in_array($item->$ind, $request->filters[$query]);
                                }
                            }
                        });
                    }
                }
            }
            return $passes;
        });
    }

    protected function sortData(Collection $collection)
    {
        if (request()->has('sort_by')) {
            if (request()->order == 'asc') {
                $collection = $collection->sortBy(request()->sort_by);
            } else {
                $collection = $collection->sortByDesc(request()->sort_by);
            }
        }
        return $collection;
    }
    protected function searchByColumn(Collection $collection)
    {
        foreach (request()->query() as $field => $value) { //request()->query() obtiene un arreglo de parametros de la url de la columna y el valor a buscar
            if (in_array($field, array_keys($collection->first()))) { // Lista de filtros permitidos obtenidos directatmente del primero elemento de la colección
                if (isset($field, $value)) { // Si el campo y el valor están definidos
                    $collection = $collection->filter(function ($item) use ($field, $value) {
                        return Str::contains(strtolower($item[$field]), strtolower($value));
                    });
                }
            }
        }
        return $collection;
    }

    protected function transformData(Collection $collection)
    {
        $resource = $collection->first()->resource;
        $collection = collect($resource::collection($collection)->toArray(request()));
        return $collection;
    }

    protected function paginate(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:50'
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
        $url = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        $fullUrl = "{$url}?{$queryString}";

        return Cache::remember($fullUrl, 30 / 60, function () use ($data) {
            return $data;
        });
    }
}
