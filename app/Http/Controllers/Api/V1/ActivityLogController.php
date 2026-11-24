<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivityLog\IndexActivityLogRequest;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Models\Activitylog;
use App\Support\Http\ApiResponse;
use App\Support\Query\Definitions\ActivityLogIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActivityLogController extends Controller implements HasMiddleware
{
    /**
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [new Middleware('can:activity.view')];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function __invoke(IndexActivityLogRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $activities = Activitylog::query();

        return ApiResponse::paginated($index->apply($activities, $request->validated(), ActivityLogIndexDefinition::definition()), ActivityLogResource::class, $request);
    }
}
