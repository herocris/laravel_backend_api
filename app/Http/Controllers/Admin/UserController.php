<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\User\StorePostRequest;
use App\Http\Requests\Admin\User\UpdatePutRequest;
use App\Http\Resources\Admin\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class UserController extends ApiController implements HasMiddleware
{
    /**
     * Define los middlewares aplicados al controlador.
     * Se utiliza un middleware de transformación que convierte la entrada
     * cruda del request en la estructura esperada por `UserResource` antes
     * de ejecutar `store` y `update`, asegurando consistencia de atributos.
     *
     * @return array<\Illuminate\Routing\Controllers\Middleware> Lista de middlewares a registrar.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . UserResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Obtiene todos los usuarios activos (sin incluir los soft-deleted).
     * No aplica paginación en este punto; si el volumen crece se recomienda
     * sustituir por `paginate()` para evitar respuestas muy grandes.
     * Devuelve colección JSON uniforme usando helper `showAll` (HTTP 200).
     *
     * @return \Illuminate\Http\JsonResponse Respuesta con arreglo de usuarios.
     */
    public function index()
    {
        $users = User::all();
        return $this->showAll($users);
    }

    /**
     * Crea un nuevo usuario con datos ya validados por `StorePostRequest`.
     * Side effects: dispara eventos de Eloquent (creating/created) y podría
     * aplicar mutadores (ej. hash de contraseña). Retorna el usuario creado
     * con código HTTP 200 (no 201, dado uso de `showOne`).
     *
     * @param StorePostRequest $request Request con reglas de validación (email único, etc.).
     * @return \Illuminate\Http\JsonResponse Usuario recién creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);
        return $this->showOne($user);
    }

    /**
     * Devuelve un usuario específico inyectado por Route Model Binding.
     * Si el ID no existe Laravel generará automáticamente un 404 antes de entrar.
     *
     * @param User $user Instancia obtenida por binding.
     * @return \Illuminate\Http\JsonResponse Representación del recurso.
     */
    public function show(User $user)
    {
        return $this->showOne($user);
    }

    /**
     * Actualiza atributos permitidos del usuario. Campos ya validados por
     * `UpdatePutRequest` (evita colisiones de email, formatos inválidos, etc.).
     * Retorna el estado final tras `save()` (HTTP 200).
     *
     * @param UpdatePutRequest $request Datos validados para actualización.
     * @param User $user Usuario objetivo.
     * @return \Illuminate\Http\JsonResponse Usuario actualizado.
     */
    public function update(UpdatePutRequest $request, User $user)
    {
        $validated = $request->validated();
        $user->update($validated);
        return $this->showOne($user);
    }

    /**
     * Realiza soft delete del usuario (marca `deleted_at`). No elimina datos
     * de forma permanente, permitiendo restauración posterior. Eventos de
     * Eloquent (deleting/deleted) pueden dispararse para auditoría.
     *
     * @param User $user Usuario a eliminar logicamente.
     * @return \Illuminate\Http\JsonResponse Usuario eliminado (estado actual).
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->showOne($user);
    }

    /**
     * Lista usuarios en papelera (soft-deleted). Útil para panel de recuperación.
     * Considerar paginación futura si el volumen crece. HTTP 200 siempre que
     * la consulta se ejecute correctamente.
     *
     * @return \Illuminate\Http\JsonResponse Colección de usuarios eliminados.
     */
    public function indexDeleted()
    {
        $users = User::onlyTrashed()->get();
        return $this->showAll($users);
    }

    /**
     * Restaura un usuario previamente soft-deleted. Requiere que el binding
     * se haga sobre un modelo con `withTrashed()` (definido en la ruta). Si
     * el usuario ya estaba activo simplemente se devuelve sin cambios.
     *
     * @param User $user Usuario a restaurar.
     * @return \Illuminate\Http\JsonResponse Usuario restaurado.
     */
    public function restore(User $user)
    {
        $user->restore();
        return $this->showOne($user);
    }
}
