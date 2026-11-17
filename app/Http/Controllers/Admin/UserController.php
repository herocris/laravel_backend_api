<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\User\StorePostRequest;
use App\Http\Requests\Admin\User\UpdatePutRequest;
use App\Http\Resources\Admin\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'nombre' => 'name',
        'correo' => 'email',
    ];

    /**
     * Define los middlewares aplicados al controlador.
     * Se utiliza un middleware de transformación que convierte la entrada
     * cruda del request en la estructura esperada por `UserResource` antes
     * de ejecutar `store` y `update`, asegurando consistencia de atributos.
     *
     * @return array<Middleware> Lista de middlewares a registrar.
     */
    public static function middleware(): array
    {
        return [
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
    public function index(): JsonResponse
    {
        $users = User::query()->with(['roles:id', 'permissions:id']);

        return $this->showAll($users, UserResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un nuevo usuario con datos ya validados por `StorePostRequest`.
     * Side effects: dispara eventos de Eloquent (creating/created) y podría
     * aplicar mutadores (ej. hash de contraseña). Retorna el usuario creado
     * con código HTTP 200 (no 201, dado uso de `showOne`).
     *
     * @param  StorePostRequest  $request  Request con reglas de validación (email único, etc.).
     * @return JsonResponse Usuario recién creado.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = (new User)->saveWithAssignments($validated);

        return $this->showOne(new UserResource($user));
    }

    /**
     * Devuelve un usuario específico inyectado por Route Model Binding.
     * Si el ID no existe Laravel generará automáticamente un 404 antes de entrar.
     *
     * @param  User  $user  Instancia obtenida por binding.
     * @return JsonResponse Representación del recurso.
     */
    public function show(User $user)
    {
        return $this->showOne(new UserResource($user));
    }

    /**
     * Actualiza atributos permitidos del usuario. Campos ya validados por
     * `UpdatePutRequest` (evita colisiones de email, formatos inválidos, etc.).
     * Retorna el estado final tras `save()` (HTTP 200).
     *
     * @param  UpdatePutRequest  $request  Datos validados para actualización.
     * @param  User  $user  Usuario objetivo.
     * @return JsonResponse Usuario actualizado.
     */
    public function update(UpdatePutRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        $user->saveWithAssignments($validated);

        return $this->showOne(new UserResource($user));
    }

    /**
     * Realiza soft delete del usuario (marca `deleted_at`). No elimina datos
     * de forma permanente, permitiendo restauración posterior. Eventos de
     * Eloquent (deleting/deleted) pueden dispararse para auditoría.
     *
     * @param  User  $user  Usuario a eliminar logicamente.
     * @return JsonResponse Usuario eliminado (estado actual).
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->showOne(new UserResource($user));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $users = User::onlyTrashed()->with(['roles:id', 'permissions:id']);

        return $this->showAll($users, UserResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un usuario previamente soft-deleted. Requiere que el binding
     * se haga sobre un modelo con `withTrashed()` (definido en la ruta). Si
     * el usuario ya estaba activo simplemente se devuelve sin cambios.
     *
     * @param  User  $user  Usuario a restaurar.
     * @return JsonResponse Usuario restaurado.
     */
    public function restore(User $user)
    {
        $user->restore();

        return $this->showOne(new UserResource($user));
    }
}
