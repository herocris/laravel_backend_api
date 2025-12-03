<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Permission\StorePostRequest;
use App\Http\Requests\Admin\Permission\UpdatePutRequest;
use App\Http\Resources\Admin\Permission\PermissionResource;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends ApiController implements HasMiddleware
{
    /**
     * Registra el middleware de transformación de entrada para garantizar
     * que los datos de creación/actualización se alineen con `PermissionResource`.
     * Esto permite mantener consistencia si se agregan alias/cambios de keys.
     *
     * @return array<\Illuminate\Routing\Controllers\Middleware> Middlewares a aplicar.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . PermissionResource::class . "", only: ['store','update']),
        ];
    }

    /**
     * Devuelve todos los permisos registrados. Útil para construcción de
     * matrices de acceso o para poblar formularios. No incluye paginación.
     *
     * @return \Illuminate\Http\JsonResponse Colección completa de permisos.
     */
    public function index()
    {
        $permissions = Permission::all();
        return $this->showAll($permissions);
    }

    /**
     * Crea un permiso nuevo tras validación (`StorePostRequest`). Side effects:
     * dispara eventos Eloquent y queda disponible inmediatamente para asignar
     * a roles. Retorna estado final (HTTP 200 por uso de `showOne`).
     *
     * @param StorePostRequest $request Datos validados.
     * @return \Illuminate\Http\JsonResponse Permiso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $permission = Permission::create($validated);
        return $this->showOne($permission);
    }

    /**
     * Muestra un permiso específico por ID (binding). Si el recurso no existe
     * Laravel genera 404 automáticamente antes de ejecutar este método.
     *
     * @param Permission $permission Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Datos del permiso.
     */
    public function show(Permission $permission)
    {
        return $this->showOne($permission);
    }

    /**
     * Actualiza campos permitidos del permiso. Ideal para ajustar nombre
     * legible o guard_name. Retorna representación actualizada (HTTP 200).
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param Permission $permission Permiso objetivo.
     * @return \Illuminate\Http\JsonResponse Permiso actualizado.
     */
    public function update(UpdatePutRequest $request, Permission $permission)
    {
        $validated = $request->validated();
        $permission->update($validated);
        return $this->showOne($permission);
    }

    /**
     * Soft delete del permiso: marca `deleted_at` sin borrar físicamente.
     * Esto permite auditoría y restauración posterior sin pérdida de integridad.
     *
     * @param Permission $permission Permiso a eliminar lógicamente.
     * @return \Illuminate\Http\JsonResponse Permiso eliminado.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $this->showOne($permission);
    }

    /**
     * Lista permisos soft-deleted para administración y posible restauración.
     * Puede requerir paginación si la lista crece significativamente.
     *
     * @return \Illuminate\Http\JsonResponse Colección de permisos eliminados.
     */
    public function indexDeleted()
    {
        $permissions = Permission::onlyTrashed()->get();
        return $this->showAll($permissions);
    }

    /**
     * Restaura un permiso previamente soft-deleted. Requiere binding con
     * `withTrashed()` en la definición de la ruta. Si ya estaba activo no cambia.
     *
     * @param Permission $permission Permiso a restaurar.
     * @return \Illuminate\Http\JsonResponse Permiso restaurado.
     */
    public function restore(Permission $permission)
    {
        $permission->restore();
        return $this->showOne($permission);
    }
}
