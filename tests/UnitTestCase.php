<?php

declare(strict_types=1);

namespace Tests;

use App\Traits\Activitylog;
use App\Traits\ApiResponser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

abstract class UnitTestCase extends BaseTestCase {}

// Proxy para exponer métodos protegidos del trait ApiResponser
class ApiResponserProxy
{
    use ApiResponser;


    public function callShowOne(Model $instance, $code = 200): JsonResponse
    {
        return $this->showOne($instance, $code);
    }

    public function callShowAll($items, $code = 200)
    {
        return $this->showAll($items, $code);
    }
    
    public function callTransformData(Collection $collection): Collection
    {
        return $this->transformData($collection);
    }

    public function callSortData(Collection $collection): Collection
    {
        return $this->sortData($collection);
    }

    public function callSearchByColumn(Collection $collection): Collection
    {
        return $this->searchByColumn($collection);
    }

    public function callPaginate(Collection $collection)
    {
        return $this->paginate($collection);
    }

    public function callCache($data)
    {
        return $this->cacheResponse($data);
    }

    public function callError($msg, $code)
    {
        return $this->errorResponse($msg, $code);
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

    public function callRecordLog($fields, $model)
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

// Modelo/entidad simple con la propiedad $resource como en los modelos reales
class FakeModel extends Model
{
    public int $id;
    public string $name;
    public string $resource = FakeResource::class;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
