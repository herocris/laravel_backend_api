<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Role\SaveRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SerializeAdministrativeMutations;
use App\Http\Requests\Api\V1\Role\IndexRoleRequest;
use App\Http\Requests\Api\V1\Role\StoreRoleRequest;
use App\Http\Requests\Api\V1\Role\UpdateRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Models\Role;
use App\Support\Authorization\AccessControl;
use App\Support\Http\ApiResponse;
use App\Support\Query\Definitions\RoleIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    public function __construct(private AccessControl $accessControl) {}

    /**
     * Registra middlewares específicos del controlador.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(SerializeAdministrativeMutations::class, only: ['store', 'update', 'destroy', 'restore']),
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
    public function index(IndexRoleRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $roles = Role::query();

        return ApiResponse::paginated($index->apply($roles, $request->validated(), RoleIndexDefinition::definition()), RoleResource::class, $request);
    }

    /**
     * Crea un nuevo rol con los datos validados y luego sincroniza los
     * permisos validados en la misma transacción. Si se omiten, el rol nace sin permisos.
     *
     * @param  StoreRoleRequest  $request  Datos validados (name, guard_name, etc.).
     * @return JsonResponse Rol creado con permisos actuales.
     */
    public function store(StoreRoleRequest $request, SaveRoleAction $action): JsonResponse
    {
        $validated = $request->validated();
        $this->accessControl->authorizePermissions($request->user(), $validated['permissions'] ?? []);
        $role = $action->execute(new Role, $validated);

        return ApiResponse::resource(new RoleResource($role->load(RoleIndexDefinition::definition()['with'])));
    }

    /**
     * Muestra un rol específico. El binding lanza 404 si no existe el ID.
     * Puedes encadenar `load('permissions')` si se requiere completo en futuro.
     *
     * @param  Role  $role  Instancia objetivo.
     * @return JsonResponse Datos del rol.
     */
    public function show(Role $role): JsonResponse
    {
        return ApiResponse::resource(new RoleResource($role->load(RoleIndexDefinition::definition()['with'])));
    }

    /**
     * Actualiza datos y permisos en la misma transacción. Omitir la lista
     * conserva sus permisos; una lista vacía los revoca.
     * Retorna estado final (HTTP 200).
     *
     * @param  UpdateRoleRequest  $request  Datos validados.
     * @param  Role  $role  Rol a actualizar.
     * @return JsonResponse Rol actualizado.
     */
    public function update(UpdateRoleRequest $request, Role $role, SaveRoleAction $action): JsonResponse
    {
        $validated = $request->validated();
        $this->accessControl->authorizeRole($request->user(), $role);
        $this->accessControl->authorizePermissions($request->user(), $validated['permissions'] ?? []);
        $action->execute($role, $validated);

        return ApiResponse::resource(new RoleResource($role->load(RoleIndexDefinition::definition()['with'])));
    }

    /**
     * Soft delete del rol (marca `deleted_at`). No elimina relaciones de
     * permisos inmediatamente; la recuperación las preserva. Evita pérdida
     * accidental y facilita auditoría.
     *
     * @param  Role  $role  Rol a eliminar lógicamente.
     * @return JsonResponse Rol eliminado.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->accessControl->authorizeRole($request->user(), $role);
        $role->delete();

        return ApiResponse::resource(new RoleResource($role->load(RoleIndexDefinition::definition()['with'])));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(IndexRoleRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $roles = Role::onlyTrashed();

        return ApiResponse::paginated($index->apply($roles, $request->validated(), RoleIndexDefinition::definition()), RoleResource::class, $request);
    }

    /**
     * Restaura un rol soft-deleted. Requiere que la ruta use binding con
     * `withTrashed()`. Si ya estaba activo no produce cambios.
     *
     * @param  Role  $role  Rol a restaurar.
     * @return JsonResponse Rol restaurado.
     */
    public function restore(Request $request, Role $role): JsonResponse
    {
        $this->accessControl->authorizeRole($request->user(), $role);
        $role->restore();

        return ApiResponse::resource(new RoleResource($role->load(RoleIndexDefinition::definition()['with'])));
    }
}
