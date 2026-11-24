<?php

namespace App\Actions\User;

use App\Models\User;
use App\Support\Authorization\AdministrativeTransaction;
use Illuminate\Support\Arr;

class SaveUserAction
{
    public function __construct(private AdministrativeTransaction $transaction) {}

    /**
     * Persiste datos administrativos ya autorizados y validados, sin depender de HTTP.
     * Las asignaciones omitidas se conservan; una lista vacía las revoca.
     *
     * @param  array{name?: string, email?: string, password?: string, roles?: list<int|string>, permissions?: list<int|string>}  $data
     */
    public function execute(User $user, array $data): User
    {
        return $this->transaction->run(function () use ($data, $user): User {
            $user->fill(Arr::only($data, ['name', 'email', 'password']));
            $user->save();

            if (array_key_exists('roles', $data)) {
                $user->syncRoles(array_map('intval', $data['roles']));
            }

            if (array_key_exists('permissions', $data)) {
                $user->syncPermissions(array_map('intval', $data['permissions']));
            }

            return $user;
        });
    }
}
