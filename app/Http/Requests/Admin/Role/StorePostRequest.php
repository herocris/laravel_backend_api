<?php

namespace App\Http\Requests\Admin\Role;

use App\Http\Requests\ApiFormRequest;
use App\Models\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Request para creación de roles.
 *
 * Valida:
 * - name: requerido, string, único en tabla roles.
 */
class StorePostRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'nombre' => 'name',
            'permisos' => 'permissions',
        ];
    }

    /**
     * Autoriza la creación administrativa y sus permisos mediante Gate.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('role.create') ?? false;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => ['sometimes', 'array', 'list'],
            'permissions.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists(Permission::class, 'id')->where('guard_name', 'api')->withoutTrashed()],
        ];
    }
}
