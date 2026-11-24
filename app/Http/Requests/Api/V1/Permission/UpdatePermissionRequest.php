<?php

namespace App\Http\Requests\Api\V1\Permission;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Request para actualización de permisos.
 *
 * Valida:
 * - name: requerido, string.
 */
class UpdatePermissionRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'nombre' => 'name',
        ];
    }

    /**
     * Autorización permitida.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->where('guard_name', $this->route('permission')->guard_name)->ignore($this->route('permission'))],
        ];
    }
}
