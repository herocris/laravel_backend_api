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
    /**
     * Registra middlewares específicos del controlador.
     * `transformInput` normaliza la estructura de entrada según `RoleResource`
     * para garantizar que las claves y formatos coincidan con la capa de
     * presentación antes de la persistencia en `store` y `update`.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role.view,api', only: ['index', 'show', 'indexDeleted']),
            new Middleware('permission:role.create,api', only: ['store']),
            new Middleware('permission:role.update,api', only: ['update', 'restore']),
            new Middleware('permission:role.delete,api', only: ['destroy']),
            new Middleware('transformInput:'.RoleResource::class.'', only: ['store', 'update']),
        ];
    }

    /**
     * Devuelve todos los roles existentes (sin filtrar por permisos).
     * Útil para poblar selects o asignaciones masivas. Considerar paginación
     * futura si la tabla crece. Respuesta HTTP 200 en caso exitoso.
     *
     * @return JsonResponse Colección de roles.
     */
    public function index()
    {
        $roles = Role::all();

        return $this->showAll($roles);
    }

    /**
     * Crea un nuevo rol con los datos validados y luego sincroniza los
     * permisos asociados. `syncPermissions` acepta nombres o IDs según
     * configuración del paquete spatie. Si no se envían permisos se asigna
     * un arreglo vacío (el rol nace sin permisos).
     *
     * @param  StorePostRequest  $request  Datos validados (name, guard_name, etc.).
     * @return JsonResponse Rol creado con permisos actuales.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create($validated);
        $role->syncPermissions(request()->permissions ?? []);

        return $this->showOne($role);
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
        return $this->showOne($role);
    }

    /**
     * Actualiza datos del rol y sincroniza su set de permisos. `syncPermissions`
     * revoca los ausentes y asigna los nuevos, manteniendo atomicidad simple.
     * Retorna estado final (HTTP 200).
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Role  $role  Rol a actualizar.
     * @return JsonResponse Rol actualizado.
     */
    public function update(UpdatePutRequest $request, Role $role)
    {
        $validated = $request->validated();
        $role->update($validated);
        $role->syncPermissions(request()->permissions ?? []);

        return $this->showOne($role);
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

        return $this->showOne($role);
    }

    /**
     * Lista roles en estado soft-deleted para permitir su restauración.
     * Ideal para panel de administración (reciclaje). No paginado actualmente.
     *
     * @return JsonResponse Colección de roles eliminados.
     */
    public function indexDeleted()
    {
        $roles = Role::onlyTrashed()->get();

        return $this->showAll($roles);
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

        return $this->showOne($role);
    }
}
