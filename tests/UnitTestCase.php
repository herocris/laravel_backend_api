<?php

declare(strict_types=1);

namespace Tests;

use App\Traits\Activitylog;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;

abstract class UnitTestCase extends BaseTestCase {}

// Proxy para exponer métodos protegidos del trait ApiResponser
class ApiResponserProxy
{
    use ApiResponser;

    public function callShowOne(Model $instance, $code = 200): JsonResponse
    {
        return $this->showOne(new FakeResource($instance), $code);
    }

    public function callShowAll(Builder $items, int $code = 200): JsonResponse
    {
        return $this->showAll($items, FakeResource::class, ['identificador' => 'id', 'nombre' => 'name'], $code);
    }

    public function callTransformData(Collection $collection): Collection
    {
        return $this->transformData($collection, FakeResource::class);
    }

    public function callSuccess($data, $code)
    {
        return $this->successResponse($data, $code);
    }
}

// Proxy para exponer métodos protegidos del trait Activitylog
class ActivitylogProxy
{
    use Activitylog;

    public function callRecordLog(array $fields, string $model): LogOptions
    {
        return $this->RecordLog($fields, $model);
    }

    // Implementación requerida por el trait Activitylog / LogsActivity
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}

// Recurso falso para comprobar transformaciones en tests unitarios
class FakeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'identificador' => $this->id,
            'nombre' => $this->name,
        ];
    }
}

// Modelo simple sin dependencia de la representación HTTP.
class FakeModel extends Model
{
    public int $id;

    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
