<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Http\Requests\Drug\StorePostRequest;
use App\Http\Requests\Drug\UpdatePutRequest;
use App\Http\Resources\Drug\DrugResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

class DrugController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares aplicados al controlador de catálogo de drogas.
     * `transformInput` armoniza la carga útil con `DrugResource` antes de
     * persistir, asegurando nombres de campos consistentes y facilitando
     * futura evolución del formato de entrada.
     *
     * @return array<Middleware> Lista de middlewares declarados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . DrugResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Devuelve todas las drogas registradas (sin paginación).
     * Recomendación: aplicar `paginate()` cuando el volumen crezca para
     * evitar respuestas muy grandes. HTTP 200 siempre que la consulta
     * concluya sin excepciones.
     *
     * @return \Illuminate\Http\JsonResponse Colección de drogas activas.
     */
    public function index()
    {
        $drugs = Drug::all();
        return $this->showAll($drugs);
    }

    /**
     * Crea una nueva droga usando datos validados por `StorePostRequest`.
     * Side effects: dispara eventos Eloquent (creating/created). No retorna
     * 201 por diseño del helper `showOne` (puede ajustarse si se requiere).
     *
     * @param StorePostRequest $request Datos validados (descripcion, logo, etc.).
     * @return \Illuminate\Http\JsonResponse Recurso recién creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drug = Drug::create($validated);
        return $this->showOne($drug);
    }

    /**
     * Muestra una droga específica mediante Route Model Binding.
     * Si el ID no existe Laravel responde 404 antes de entrar al método.
     *
     * @param Drug $drug Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Representación del recurso.
     */
    public function show(Drug $drug)
    {
        return $this->showOne($drug);
    }

    /**
     * Actualiza atributos permitidos de una droga. Campos verificados por
     * `UpdatePutRequest`. Retorna estado final tras persistencia.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param Drug $drug Modelo a actualizar.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Drug $drug)
    {
        $validated = $request->validated();
        $drug->update($validated);
        return $this->showOne($drug);
    }

    /**
     * Soft delete (marca `deleted_at`) de la droga. Preserva datos para
     * futura restauración y auditoría. No elimina relaciones dependientes.
     *
     * @param Drug $drug Recurso a eliminar lógicamente.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(Drug $drug)
    {
        $drug->delete();
        return $this->showOne($drug);
    }

    /**
     * Lista drogas en estado soft-deleted para panel de recuperación.
     * Puede requerir paginación futura.
     *
     * @return \Illuminate\Http\JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $drugs = Drug::onlyTrashed()->get();
        return $this->showAll($drugs);
    }

    /**
     * Restaura una droga previamente eliminada. Requiere que la ruta use
     * binding con `withTrashed()`. Si ya estaba activa no cambia estado.
     *
     * @param Drug $drug Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(Drug $drug)
    {
        $drug->restore();
        return $this->showOne($drug);
    }
}
