<?php

namespace App\Traits;

use App\Http\Requests\DrugConfiscation\GetRequest as DrugRequest;
use App\Http\Requests\WeaponConfiscation\GetRequest as WeaponRequest;
use App\Http\Requests\AmmunitionConfiscation\GetRequest as AmmunitionRequest;
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
        return match ($period) {
            //'day' => "DATE(full_date)",
            'month' => "DATE_FORMAT(full_date, '%Y-%m')",
            // 'quarter' => "CONCAT(YEAR(full_date), '-Q', QUARTER(full_date))",
            // 'semester' => "CONCAT(YEAR(full_date), '-S', IF(MONTH(full_date) <= 6, 1, 2))",
            'year' => "YEAR(full_date)",
            default => "DATE(full_date)", // Periodo total
        };
    }

    protected function generatePeriods($startDate, $endDate, $periodType)
    {
        $periodos = collect();
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        if ($periodType === 'total') {
            return collect(["$startDate a $endDate"]);
        }

        while ($currentDate <= $endDate) {
            switch ($periodType) {
                case 'day':
                    $formattedPeriod = $currentDate->format('Y-m-d');
                    $currentDate->addDay();
                    break;

                case 'month':
                    $formattedPeriod = $currentDate->format('Y-m');
                    $currentDate->addMonth();
                    break;

                case 'quarter':
                    $formattedPeriod = $currentDate->year . '-Q' . ceil($currentDate->month / 3);
                    $currentDate->addMonths(3);
                    break;

                case 'semester':
                    $formattedPeriod = $currentDate->year . '-S' . ($currentDate->month <= 6 ? '1' : '2');
                    $currentDate->addMonths(6);
                    break;

                case 'year':
                    $formattedPeriod = $currentDate->format('Y');
                    $currentDate->addYear();
                    break;

                default:
                    $formattedPeriod = $currentDate->format('Y-m-d');
                    $currentDate->addDay();
            }

            $periodos->push($formattedPeriod);
        }

        return $periodos;
    }

    protected function ConfiscationId($criteria)
    {
        return match ($criteria) {
            'drugs' => "drug_id",
            'presentations' => "drug_presentation_id",
            'weapons' => "weapon_id",
            'ammunitions' => "ammunition_id",
        };
    }

    protected function ConfiscationName($nameConfiscations, $idConfiscation, $typeConfiscation)
    {
        $name = $nameConfiscations->firstWhere($this->ConfiscationId($typeConfiscation), $idConfiscation);
        return $name[$this->ConfiscationDescription($typeConfiscation)];
    }

    protected function FormatCollectionBarLine(Collection $queryResult, $criteria)
    {
        $linedata = collect($queryResult) //formateando data para grafica de linea
            ->groupBy('period')
            ->map(function ($group, $period) use ($criteria) {
                $entry = ['name' => $period];
                foreach ($group as $item) {
                    $entry[$item->{$this->ConfiscationDescription($criteria)}] = $item->total_amount; //accediendo de manera dinamica a las propiedades de un item
                }
                return $entry;
            })
            ->values(); // para tener índices numéricos (array plano)
        return $linedata;
    }
    protected function FormatCollectionPie(Collection $queryResult, $criteria)
    {
        $pieData = collect($queryResult) //formateando data para grafica de pastel
            ->groupBy($this->ConfiscationDescription($criteria))
            ->map(function ($group, $name) {
                return [
                    'name' => $name,
                    'value' => $group->sum('total_amount')
                ];
            })
            ->values(); // para tener índices numéricos (array plano)
        return $pieData;
    }

    protected function ConfiscationDescription($typeConfiscation)
    {
        return match ($typeConfiscation) {
            'drugs' => "drug_description",
            'presentations' => "drug_presentation_description",
            'weapons' => "weapon_description",
            'ammunitions' => "ammunition_description",
        };
    }




    protected function queryFormater(Collection $queryResult, AmmunitionRequest|DrugRequest|WeaponRequest $request)
    {
        $periodType = $request->input('period');
        $typeConfiscation = $request->input('typeConfiscation');
        $magnitude = $request->input('magnitude');

        $typeConfiscationsIds = json_decode($request->input($typeConfiscation) ?? '[]');
        $periodos = $this->generatePeriods($request->input('start_date'), $request->input('end_date'), $periodType);

        $namesConfiscations = $queryResult
            ->unique($this->ConfiscationId($typeConfiscation))
            ->select($this->ConfiscationDescription($typeConfiscation), $this->ConfiscationId($typeConfiscation));
        // Agrupar los resultados por período
        $groupedResults = $queryResult->groupBy('period');

        $grouped = collect($typeConfiscationsIds)->mapWithKeys(function ($typeConfiscation_id) use ($groupedResults, $periodos, $periodType, $typeConfiscation, $magnitude, $namesConfiscations) {
            $filtered = $groupedResults->map(
                fn($items) =>
                $items->where($this->ConfiscationId($typeConfiscation), $typeConfiscation_id)
            );
            $data = ($periodType === 'total')
                ? [$filtered->flatten()->sum($magnitude == 'amount' ? 'total_amount' : 'total_weight')]
                : $periodos->map(
                    fn($period) =>
                    optional($filtered->get($period))->sum($magnitude == 'amount' ? 'total_amount' : 'total_weight') ?? 0
                )->toArray();

            $typeConfiscationName = ($typeConfiscation == 'drugs')
                ? Drug::find($typeConfiscation_id)->description
                : DrugPresentation::find($typeConfiscation_id)->description;

            return [$typeConfiscation_id => [
                "atributo cualquiera 1" => "contenido de atributo1",
                "label" => $this->ConfiscationName($namesConfiscations, $typeConfiscation_id, $typeConfiscation),
                "data" => $data
            ]];
        });

        return [
            "datasets" => $grouped->values()->toArray(),
            "periodos" => $periodos->toArray()
        ];
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
            if (request()->type == 'asc') {
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
            'per_page' => 'integer|min:2|max:100'
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
