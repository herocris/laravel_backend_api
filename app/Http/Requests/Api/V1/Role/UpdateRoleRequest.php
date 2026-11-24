<?php

namespace App\Http\Requests\Api\V1\Role;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Request para actualización de roles.
 *
 * Valida:
 * - name: requerido, string.
 */
class UpdateRoleRequest extends ApiFormRequest
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
     * Autoriza la gestión de roles del guard efectivo mediante Gate.
     */
    public function authorize(): bool
    {
        return ($this->user()?->can('role.update') ?? false)
            && $this->route('role')->guard_name === 'api';
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where('guard_name', $this->route('role')->guard_name)->ignore($this->route('role'))],
            'permissions' => ['sometimes', 'array', 'list'],
            'permissions.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists(Permission::class, 'id')->where('guard_name', 'api')->withoutTrashed()],
        ];
    }
}
