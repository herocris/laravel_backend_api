<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Request para actualización de usuarios.
 *
 * Valida:
 * - name: requerido, string.
 * - email: requerido, email único excepto para el propio registro (usa Rule::unique con ignore).
 * - password: mínimo 6 caracteres.
 */
class UpdateUserRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'nombre' => 'name',
            'correo' => 'email',
            'password' => 'password',
            'roles' => 'roles',
            'permisos' => 'permissions',
        ];
    }

    /**
     * Autoriza la actualización administrativa y sus asignaciones mediante Gate.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('user.update') ?? false;
    }

    /**
     * Reglas de validación para actualización de usuario.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->route('user')->id), // para validar si se esta actualizando el correo con uno ya existente
            ],
            // 'password' => ['required',
            // Password::min(8)
            // ->letters()
            // ->mixedCase()
            // ->numbers()
            // ->symbols()],
            'password' => 'required|string|min:6',
            'roles' => ['sometimes', 'array', 'list'],
            'roles.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists(Role::class, 'id')->where('guard_name', 'api')->withoutTrashed()],
            'permissions' => ['sometimes', 'array', 'list'],
            'permissions.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists(Permission::class, 'id')->where('guard_name', 'api')->withoutTrashed()],
        ];
    }
}
