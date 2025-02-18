<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

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

    protected function showOne(Model $instance, $code = 200)
	{
		//$transformer = $instance->transformer;
		//$instance = $this->transformData($instance, $transformer);

		return $this->successResponse($instance, $code);
	}
    protected function showAll(Collection $collection, $code = 200)
    {
        if ($collection->isEmpty()) {
        	return $this->successResponse(['data' => $collection], $code);
        }

        $collection = $this->filterData($collection);
        $collection = $this->sortData($collection);
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
                    if ($relation instanceof \Illuminate\Database\Eloquent\Collection) {// cuando la relación es de muchos a muchos
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
            if (request()->sort_by_order == 'asc') {
                $collection = $collection->sortBy(request()->sort_by);
            } else {
                $collection = $collection->sortByDesc(request()->sort_by);
            }
        }
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
