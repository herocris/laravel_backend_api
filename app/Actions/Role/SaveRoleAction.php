<?php

namespace App\Actions\Role;

use App\Models\Role;
use App\Support\Authorization\AdministrativeTransaction;
use Illuminate\Support\Arr;

class SaveRoleAction
{
    public function __construct(private AdministrativeTransaction $transaction) {}

    /**
     * Persiste datos administrativos ya autorizados y validados, sin depender de HTTP.
     * Los permisos omitidos se conservan; una lista vacía los revoca.
     *
     * @param  array{name?: string, permissions?: list<int|string>}  $data
     */
    public function execute(Role $role, array $data): Role
    {
        return $this->transaction->run(function () use ($data, $role): Role {
            $role->fill(Arr::only($data, ['name']));
            if (! $role->exists) {
                $role->guard_name = 'api';
            }
            $role->save();

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions(array_map('intval', $data['permissions']));
            }

            return $role;
        });
    }
}
