<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SerializeAdministrativeMutations;
use App\Http\Requests\Api\V1\Permission\IndexPermissionRequest;
use App\Http\Requests\Api\V1\Permission\StorePermissionRequest;
use App\Http\Requests\Api\V1\Permission\UpdatePermissionRequest;
use App\Http\Resources\Api\V1\PermissionResource;
use App\Models\Permission;
use App\Support\Authorization\AccessControl;
use App\Support\Query\Definitions\PermissionIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    public function __construct(private AccessControl $accessControl) {}

    /**
     * Define los permisos por operación del controlador.
     *
     * @return array<Middleware> Middlewares a aplicar.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(SerializeAdministrativeMutations::class, only: ['store', 'update', 'destroy', 'restore']),
            new Middleware('can:permission.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:permission.create', only: ['store']),
            new Middleware('can:permission.update', only: ['update', 'restore']),
            new Middleware('can:permission.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexPermissionRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $permissions = Permission::query();

        $items = $index->apply($permissions, $request->validated(), PermissionIndexDefinition::definition());

        return PermissionResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Permission records retrieved successfully.',
        ]);
    }

    /**
     * Crea un permiso nuevo tras validación (`StorePermissionRequest`). Side effects:
     * dispara eventos Eloquent y queda disponible inmediatamente para asignar
     * a roles. Retorna estado final (HTTP 201).
     *
     * @param  StorePermissionRequest  $request  Datos validados.
     * @return JsonResponse Permiso creado.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $permission = Permission::create($validated);

        return (new PermissionResource($permission))->additional([
            'success' => true,
            'message' => 'Permission created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un permiso específico por ID (binding). Si el recurso no existe
     * Laravel genera 404 automáticamente antes de ejecutar este método.
     *
     * @param  Permission  $permission  Instancia objetivo.
     * @return PermissionResource Datos del permiso.
     */
    public function show(Permission $permission): PermissionResource
    {
        return (new PermissionResource($permission))->additional([
            'success' => true,
            'message' => 'Permission retrieved successfully.',
        ]);
    }

    /**
     * Actualiza campos permitidos del permiso. Ideal para ajustar nombre
     * legible o guard_name. Retorna representación actualizada (HTTP 200).
     *
     * @param  UpdatePermissionRequest  $request  Datos validados.
     * @param  Permission  $permission  Permiso objetivo.
     * @return PermissionResource Permiso actualizado.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $validated = $request->validated();
        $this->accessControl->authorizePermissionRename($request->user(), $permission, $validated['name']);
        $permission->update($validated);

        return (new PermissionResource($permission))->additional([
            'success' => true,
            'message' => 'Permission updated successfully.',
        ]);
    }

    /**
     * Soft delete del permiso: marca `deleted_at` sin borrar físicamente.
     * Esto permite auditoría y restauración posterior sin pérdida de integridad.
     *
     * @param  Permission  $permission  Permiso a eliminar lógicamente.
     * @return PermissionResource Permiso eliminado.
     */
    public function destroy(Request $request, Permission $permission): PermissionResource
    {
        $this->accessControl->authorizePermissionTargets($request->user(), $permission);
        $permission->delete();

        return (new PermissionResource($permission))->additional([
            'success' => true,
            'message' => 'Permission deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexPermissionRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $permissions = Permission::onlyTrashed();

        $items = $index->apply($permissions, $request->validated(), PermissionIndexDefinition::definition());

        return PermissionResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Permission records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un permiso previamente soft-deleted. Requiere binding con
     * `withTrashed()` en la definición de la ruta. Si ya estaba activo no cambia.
     *
     * @param  Permission  $permission  Permiso a restaurar.
     * @return PermissionResource Permiso restaurado.
     */
    public function restore(Request $request, Permission $permission): PermissionResource
    {
        $this->accessControl->authorizePermissionTargets($request->user(), $permission);
        $permission->restore();

        return (new PermissionResource($permission))->additional([
            'success' => true,
            'message' => 'Permission restored successfully.',
        ]);
    }
}
