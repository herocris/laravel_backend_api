<?php

namespace App\Http\Controllers;

use App\Http\Requests\DrugPresentation\StorePostRequest;
use App\Http\Requests\DrugPresentation\UpdatePutRequest;
use App\Http\Resources\DrugPresentation\DrugPresentationResource;
use App\Models\DrugPresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DrugPresentationController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares aplicados al controlador de presentaciones de droga.
     * Garantiza transformación consistente de la data mediante `DrugPresentationResource`.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:drugPresentation.view,api', only: ['index', 'show', 'indexDeleted']),
            new Middleware('permission:drugPresentation.create,api', only: ['store']),
            new Middleware('permission:drugPresentation.update,api', only: ['update', 'restore']),
            new Middleware('permission:drugPresentation.delete,api', only: ['destroy']),
            new Middleware('transformInput:'.DrugPresentationResource::class.'', only: ['store', 'update']),
        ];
    }

    /**
     * Lista todas las presentaciones registradas.
     *
     * @return JsonResponse Colección de presentaciones.
     */
    public function index()
    {
        $drugPresentations = DrugPresentation::all();

        return $this->showAll($drugPresentations);
    }

    /**
     * Crea una nueva presentación con datos validados.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drugPresentation = DrugPresentation::create($validated);

        return $this->showOne($drugPresentation);
    }

    /**
     * Muestra una presentación específica.
     *
     * @param  DrugPresentation  $drugPresentation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(DrugPresentation $drugPresentation)
    {
        return $this->showOne($drugPresentation);
    }

    /**
     * Actualiza una presentación existente.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  DrugPresentation  $drugPresentation  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, DrugPresentation $drugPresentation)
    {
        $validated = $request->validated();
        $drugPresentation->update($validated);

        return $this->showOne($drugPresentation);
    }

    /**
     * Soft delete de la presentación.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(DrugPresentation $drugPresentation)
    {
        $drugPresentation->delete();

        return $this->showOne($drugPresentation);
    }

    /**
     * Lista presentaciones en papelera.
     *
     * @return JsonResponse Colección soft-deleted.
     */
    public function indexDeleted()
    {
        $drugPresentations = DrugPresentation::onlyTrashed()->get();

        return $this->showAll($drugPresentations);
    }

    /**
     * Restaura una presentación eliminada.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(DrugPresentation $drugPresentation)
    {
        $drugPresentation->restore();

        return $this->showOne($drugPresentation);
    }
}
