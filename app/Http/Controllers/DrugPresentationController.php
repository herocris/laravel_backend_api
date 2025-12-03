<?php

namespace App\Http\Controllers;

use App\Models\DrugPresentation;
use App\Http\Requests\DrugPresentation\StorePostRequest;
use App\Http\Requests\DrugPresentation\UpdatePutRequest;
use App\Http\Resources\DrugPresentation\DrugPresentationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

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
            new Middleware("transformInput:" . DrugPresentationResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Lista todas las presentaciones registradas.
     *
     * @return \Illuminate\Http\JsonResponse Colección de presentaciones.
     */
    public function index()
    {
        $drugPresentations = DrugPresentation::all();
        return $this->showAll($drugPresentations);
    }

    /**
     * Crea una nueva presentación con datos validados.
     *
     * @param StorePostRequest $request Datos validados.
     * @return \Illuminate\Http\JsonResponse Recurso creado.
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
     * @param DrugPresentation $drugPresentation Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso solicitado.
     */
    public function show(DrugPresentation $drugPresentation)
    {
        return $this->showOne($drugPresentation);
    }

    /**
     * Actualiza una presentación existente.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param DrugPresentation $drugPresentation Recurso a actualizar.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
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
     * @param DrugPresentation $drugPresentation Recurso a eliminar.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(DrugPresentation $drugPresentation)
    {
        $drugPresentation->delete();
        return $this->showOne($drugPresentation);
    }

    /**
     * Lista presentaciones en papelera.
     *
     * @return \Illuminate\Http\JsonResponse Colección soft-deleted.
     */
    public function indexDeleted()
    {
        $drugPresentations = DrugPresentation::onlyTrashed()->get();
        return $this->showAll($drugPresentations);
    }

    /**
     * Restaura una presentación eliminada.
     *
     * @param DrugPresentation $drugPresentation Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(DrugPresentation $drugPresentation)
    {
        $drugPresentation->restore();
        return $this->showOne($drugPresentation);
    }
}
