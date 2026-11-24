<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Models\Concerns\TracksActivity;
use App\Support\Http\ApiResponse;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\LogOptions;

abstract class UnitTestCase extends BaseTestCase {}

// Adaptador de las pruebas históricas a las clases de consulta y respuesta.
class ApiResponserProxy
{
    public function callShowOne(Model $instance, $code = 200): JsonResponse
    {
        return ApiResponse::resource(new FakeResource($instance), $code);
    }

    public function callShowAll(Builder $items, int $code = 200): JsonResponse
    {
        $fields = ['identificador' => 'id', 'nombre' => 'name'];
        $parameters = Validator::validate(request()->query(), IndexRequest::rulesFor($fields));
        $paginator = (new IndexQueryApplier)->apply($items, $parameters, ['fields' => $fields]);

        return ApiResponse::paginated($paginator, FakeResource::class, request(), $code);
    }

    public function callTransformData(Collection $collection): Collection
    {
        return ApiResponse::transform($collection, FakeResource::class, request());
    }

    public function callSuccess($data, $code)
    {
        return ApiResponse::success($data, $code);
    }
}

// Proxy para exponer métodos protegidos del trait TracksActivity
class ActivitylogProxy
{
    use TracksActivity;

    public function callRecordLog(array $fields, string $model): LogOptions
    {
        return $this->RecordLog($fields, $model);
    }

    // Implementación requerida por el trait TracksActivity / LogsActivity
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
