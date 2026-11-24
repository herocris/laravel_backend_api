<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\SaveUserAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SerializeAdministrativeMutations;
use App\Http\Requests\Api\V1\User\IndexUserRequest;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Authorization\AccessControl;
use App\Support\Http\ApiResponse;
use App\Support\Query\Definitions\UserIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(private AccessControl $accessControl) {}

    /**
     * Define los permisos por operación del controlador.
     *
     * @return array<Middleware> Lista de middlewares a registrar.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(SerializeAdministrativeMutations::class, only: ['store', 'update', 'destroy', 'restore']),
            new Middleware('can:user.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:user.create', only: ['store']),
            new Middleware('can:user.update', only: ['update', 'restore']),
            new Middleware('can:user.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(IndexUserRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $users = User::query();

        return ApiResponse::paginated($index->apply($users, $request->validated(), UserIndexDefinition::definition()), UserResource::class, $request);
    }

    /**
     * Crea un nuevo usuario con datos ya validados por `StoreUserRequest`.
     * Side effects: dispara eventos de Eloquent (creating/created) y podría
     * aplicar mutadores (ej. hash de contraseña). Retorna el usuario creado
     * con código HTTP 200 (no 201, dado uso de `ApiResponse::resource`).
     *
     * @param  StoreUserRequest  $request  Request con reglas de validación (email único, etc.).
     * @return JsonResponse Usuario recién creado.
     */
    public function store(StoreUserRequest $request, SaveUserAction $action): JsonResponse
    {
        $validated = $request->validated();
        $this->accessControl->authorizePermissions($request->user(), $validated['permissions'] ?? []);
        $this->accessControl->authorizeRoles($request->user(), $validated['roles'] ?? []);
        $user = $action->execute(new User, $validated);

        return ApiResponse::resource(new UserResource($user->load(UserIndexDefinition::definition()['with'])));
    }

    /**
     * Devuelve un usuario específico inyectado por Route Model Binding.
     * Si el ID no existe Laravel generará automáticamente un 404 antes de entrar.
     *
     * @param  User  $user  Instancia obtenida por binding.
     * @return JsonResponse Representación del recurso.
     */
    public function show(User $user): JsonResponse
    {
        return ApiResponse::resource(new UserResource($user->load(UserIndexDefinition::definition()['with'])));
    }

    /**
     * Actualiza atributos permitidos del usuario. Campos ya validados por
     * `UpdateUserRequest` (evita colisiones de email, formatos inválidos, etc.).
     * Retorna el estado final tras `save()` (HTTP 200).
     *
     * @param  UpdateUserRequest  $request  Datos validados para actualización.
     * @param  User  $user  Usuario objetivo.
     * @return JsonResponse Usuario actualizado.
     */
    public function update(UpdateUserRequest $request, User $user, SaveUserAction $action): JsonResponse
    {
        $validated = $request->validated();
        $this->accessControl->authorizeUser($request->user(), $user);
        $this->accessControl->authorizePermissions($request->user(), $validated['permissions'] ?? []);
        $this->accessControl->authorizeRoles($request->user(), $validated['roles'] ?? []);
        $action->execute($user, $validated);

        return ApiResponse::resource(new UserResource($user->load(UserIndexDefinition::definition()['with'])));
    }

    /**
     * Realiza soft delete del usuario (marca `deleted_at`). No elimina datos
     * de forma permanente, permitiendo restauración posterior. Eventos de
     * Eloquent (deleting/deleted) pueden dispararse para auditoría.
     *
     * @param  User  $user  Usuario a eliminar logicamente.
     * @return JsonResponse Usuario eliminado (estado actual).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->accessControl->authorizeUser($request->user(), $user);
        $user->delete();

        return ApiResponse::resource(new UserResource($user->load(UserIndexDefinition::definition()['with'])));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(IndexUserRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $users = User::onlyTrashed();

        return ApiResponse::paginated($index->apply($users, $request->validated(), UserIndexDefinition::definition()), UserResource::class, $request);
    }

    /**
     * Restaura un usuario previamente soft-deleted. Requiere que el binding
     * se haga sobre un modelo con `withTrashed()` (definido en la ruta). Si
     * el usuario ya estaba activo simplemente se devuelve sin cambios.
     *
     * @param  User  $user  Usuario a restaurar.
     * @return JsonResponse Usuario restaurado.
     */
    public function restore(Request $request, User $user): JsonResponse
    {
        $this->accessControl->authorizeUser($request->user(), $user);
        $user->restore();

        return ApiResponse::resource(new UserResource($user->load(UserIndexDefinition::definition()['with'])));
    }
}
