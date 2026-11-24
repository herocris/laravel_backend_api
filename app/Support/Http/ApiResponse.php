<?php

namespace App\Support\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ApiResponse
{
    public static function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    public static function resource(JsonResource $resource, int $status = 200): JsonResponse
    {
        return self::success($resource, $status);
    }

    /** @param class-string<JsonResource> $resource */
    public static function paginated(LengthAwarePaginator $paginator, string $resource, Request $request, int $status = 200): JsonResponse
    {
        $parameters = $request->query();
        if (array_key_exists('include', $parameters) && $parameters['include'] === []) {
            $parameters['include'] = '';
        }
        $paginator->appends($parameters);
        $paginator->setCollection(self::transform($paginator->getCollection(), $resource, $request));

        return self::success($paginator, $status);
    }

    /** @param class-string<JsonResource> $resource */
    public static function transform(Collection $collection, string $resource, Request $request): Collection
    {
        return collect($resource::collection($collection)->resolve($request));
    }
}
