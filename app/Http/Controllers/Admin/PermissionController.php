<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Permission\StorePostRequest;
use App\Http\Requests\Admin\Permission\UpdatePutRequest;
use App\Http\Resources\Admin\Permission\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'nombre' => 'name',
    ];

    /**
     * Registra el middleware de transformación de entrada para garantizar
     * que los datos de creación/actualización se alineen con `PermissionResource`.
     * Esto permite mantener consistencia si se agregan alias/cambios de keys.
     *
     * @return array<Middleware> Middlewares a aplicar.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:permission.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:permission.create', only: ['store']),
            new Middleware('can:permission.update', only: ['update', 'restore']),
            new Middleware('can:permission.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::query();

        return $this->showAll($permissions, PermissionResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un permiso nuevo tras validación (`StorePostRequest`). Side effects:
     * dispara eventos Eloquent y queda disponible inmediatamente para asignar
     * a roles. Retorna estado final (HTTP 200 por uso de `showOne`).
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Permiso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $permission = Permission::create($validated);

        return $this->showOne(new PermissionResource($permission));
    }

    /**
     * Muestra un permiso específico por ID (binding). Si el recurso no existe
     * Laravel genera 404 automáticamente antes de ejecutar este método.
     *
     * @param  Permission  $permission  Instancia objetivo.
     * @return JsonResponse Datos del permiso.
     */
    public function show(Permission $permission)
    {
        return $this->showOne(new PermissionResource($permission));
    }

    /**
     * Actualiza campos permitidos del permiso. Ideal para ajustar nombre
     * legible o guard_name. Retorna representación actualizada (HTTP 200).
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Permission  $permission  Permiso objetivo.
     * @return JsonResponse Permiso actualizado.
     */
    public function update(UpdatePutRequest $request, Permission $permission)
    {
        $validated = $request->validated();
        $permission->update($validated);

        return $this->showOne(new PermissionResource($permission));
    }

    /**
     * Soft delete del permiso: marca `deleted_at` sin borrar físicamente.
     * Esto permite auditoría y restauración posterior sin pérdida de integridad.
     *
     * @param  Permission  $permission  Permiso a eliminar lógicamente.
     * @return JsonResponse Permiso eliminado.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return $this->showOne(new PermissionResource($permission));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $permissions = Permission::onlyTrashed();

        return $this->showAll($permissions, PermissionResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un permiso previamente soft-deleted. Requiere binding con
     * `withTrashed()` en la definición de la ruta. Si ya estaba activo no cambia.
     *
     * @param  Permission  $permission  Permiso a restaurar.
     * @return JsonResponse Permiso restaurado.
     */
    public function restore(Permission $permission)
    {
        $permission->restore();

        return $this->showOne(new PermissionResource($permission));
    }
}
