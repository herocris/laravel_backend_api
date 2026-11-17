<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Role\StorePostRequest;
use App\Http\Requests\Admin\Role\UpdatePutRequest;
use App\Http\Resources\Admin\Role\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'nombre' => 'name',
    ];

    /**
     * Registra middlewares específicos del controlador.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:role.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:role.create', only: ['store']),
            new Middleware('can:role.update', only: ['update', 'restore']),
            new Middleware('can:role.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $roles = Role::query()->with(['permissions:id']);

        return $this->showAll($roles, RoleResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un nuevo rol con los datos validados y luego sincroniza los
     * permisos validados en la misma transacción. Si se omiten, el rol nace sin permisos.
     *
     * @param  StorePostRequest  $request  Datos validados (name, guard_name, etc.).
     * @return JsonResponse Rol creado con permisos actuales.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = (new Role)->saveWithPermissions($validated);

        return $this->showOne(new RoleResource($role));
    }

    /**
     * Muestra un rol específico. El binding lanza 404 si no existe el ID.
     * Puedes encadenar `load('permissions')` si se requiere completo en futuro.
     *
     * @param  Role  $role  Instancia objetivo.
     * @return JsonResponse Datos del rol.
     */
    public function show(Role $role)
    {
        return $this->showOne(new RoleResource($role));
    }

    /**
     * Actualiza datos y permisos en la misma transacción. Omitir la lista
     * conserva sus permisos; una lista vacía los revoca.
     * Retorna estado final (HTTP 200).
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Role  $role  Rol a actualizar.
     * @return JsonResponse Rol actualizado.
     */
    public function update(UpdatePutRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();
        $role->saveWithPermissions($validated);

        return $this->showOne(new RoleResource($role));
    }

    /**
     * Soft delete del rol (marca `deleted_at`). No elimina relaciones de
     * permisos inmediatamente; la recuperación las preserva. Evita pérdida
     * accidental y facilita auditoría.
     *
     * @param  Role  $role  Rol a eliminar lógicamente.
     * @return JsonResponse Rol eliminado.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return $this->showOne(new RoleResource($role));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $roles = Role::onlyTrashed()->with(['permissions:id']);

        return $this->showAll($roles, RoleResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un rol soft-deleted. Requiere que la ruta use binding con
     * `withTrashed()`. Si ya estaba activo no produce cambios.
     *
     * @param  Role  $role  Rol a restaurar.
     * @return JsonResponse Rol restaurado.
     */
    public function restore(Role $role)
    {
        $role->restore();

        return $this->showOne(new RoleResource($role));
    }
}
