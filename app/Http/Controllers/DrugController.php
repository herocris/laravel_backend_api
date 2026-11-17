<?php

namespace App\Http\Controllers;

use App\Helpers\PersistUploadedImage;
use App\Http\Requests\Drug\StorePostRequest;
use App\Http\Requests\Drug\UpdatePutRequest;
use App\Http\Resources\Drug\DrugResource;
use App\Models\Drug;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DrugController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'descripcion' => 'description',
        'logo' => 'logo',
    ];

    /**
     * Middlewares aplicados al controlador de catálogo de drogas.
     *
     * @return array<Middleware> Lista de middlewares declarados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:drug.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:drug.create', only: ['store']),
            new Middleware('can:drug.update', only: ['update', 'restore']),
            new Middleware('can:drug.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $drugs = Drug::query();

        return $this->showAll($drugs, DrugResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea una nueva droga usando datos validados por `StorePostRequest`.
     * Side effects: dispara eventos Eloquent (creating/created). No retorna
     * 201 por diseño del helper `showOne` (puede ajustarse si se requiere).
     *
     * @param  StorePostRequest  $request  Datos validados (descripcion, logo, etc.).
     * @return JsonResponse Recurso recién creado.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $drug = $images->save(new Drug, $validated, 'logo', 'drug', 'public');

        return $this->showOne(new DrugResource($drug));
    }

    /**
     * Muestra una droga específica mediante Route Model Binding.
     * Si el ID no existe Laravel responde 404 antes de entrar al método.
     *
     * @param  Drug  $drug  Instancia objetivo.
     * @return JsonResponse Representación del recurso.
     */
    public function show(Drug $drug)
    {
        return $this->showOne(new DrugResource($drug));
    }

    /**
     * Actualiza atributos permitidos de una droga. Campos verificados por
     * `UpdatePutRequest`. Retorna estado final tras persistencia.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Drug  $drug  Modelo a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Drug $drug, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($drug, $validated, 'logo', 'drug', 'public');

        return $this->showOne(new DrugResource($drug));
    }

    /**
     * Soft delete (marca `deleted_at`) de la droga. Preserva datos para
     * futura restauración y auditoría. No elimina relaciones dependientes.
     *
     * @param  Drug  $drug  Recurso a eliminar lógicamente.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(Drug $drug)
    {
        $drug->delete();

        return $this->showOne(new DrugResource($drug));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $drugs = Drug::onlyTrashed();

        return $this->showAll($drugs, DrugResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura una droga previamente eliminada. Requiere que la ruta use
     * binding con `withTrashed()`. Si ya estaba activa no cambia estado.
     *
     * @param  Drug  $drug  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(Drug $drug)
    {
        $drug->restore();

        return $this->showOne(new DrugResource($drug));
    }
}
