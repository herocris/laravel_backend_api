<?php

namespace App\Support\Authorization;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class AccessControl
{
    /** @param list<int|string> $permissionIds */
    public function authorizePermissions(User $actor, array $permissionIds): void
    {
        $ownedIds = $this->ownedPermissions($actor)->pluck('id')->map(intval(...))->all();

        if (array_diff(array_map(intval(...), $permissionIds), $ownedIds) !== []) {
            throw new AuthorizationException('No puede gestionar permisos que no posee.');
        }
    }

    /** @param list<int|string> $roleIds */
    public function authorizeRoles(User $actor, array $roleIds): void
    {
        $ids = array_unique(array_map(intval(...), $roleIds));
        $roles = Role::query()->where('guard_name', 'api')->whereKey($ids)->with([
            'permissions' => fn (BelongsToMany $query) => $query->withTrashed(),
        ])->get();

        if ($roles->count() !== count($ids)) {
            throw new AuthorizationException('No puede asignar estos roles.');
        }

        $this->authorizePermissions($actor, $roles->flatMap(fn (Role $role) => $role->permissions->pluck('id'))->all());
    }

    public function authorizeUser(User $actor, User $target): void
    {
        $roles = $target->roles()->withTrashed()->with([
            'permissions' => fn (BelongsToMany $query) => $query->withTrashed(),
        ])->get();
        $permissions = $target->permissions()->withTrashed()->pluck('permissions.id');

        $this->authorizePermissions($actor, $permissions
            ->merge($roles->flatMap(fn (Role $role) => $role->permissions->pluck('id')))
            ->unique()->values()->all());
    }

    public function authorizeRole(User $actor, Role $role): void
    {
        if ($role->guard_name !== 'api') {
            throw new AuthorizationException('El rol no pertenece al guard api.');
        }

        $this->authorizePermissions($actor, $role->permissions()->withTrashed()->pluck('permissions.id')->all());
    }

    public function authorizePermissionTargets(User $actor, Permission $permission): void
    {
        foreach ($permission->roles()->withTrashed()->get() as $role) {
            $this->authorizeRole($actor, $role);
        }

        foreach ($permission->users()->withTrashed()->get() as $user) {
            $this->authorizeUser($actor, $user);
        }
    }

    public function authorizePermissionRename(User $actor, Permission $permission, string $name): void
    {
        $this->authorizePermissionTargets($actor, $permission);

        if ($name !== $permission->name
            && ($permission->roles()->withTrashed()->exists() || $permission->users()->withTrashed()->exists())
            && ! $this->ownedPermissions($actor)->contains('name', $name)) {
            throw new AuthorizationException('No puede conceder otro permiso mediante un cambio de nombre.');
        }
    }

    /** @return Collection<int, Permission> */
    private function ownedPermissions(User $actor): Collection
    {
        $current = $actor->fresh();
        if ($current === null || $current->trashed()) {
            throw new AuthorizationException('El actor no está activo.');
        }

        return $current->load(['roles.permissions', 'permissions'])
            ->getAllPermissions()->where('guard_name', 'api')->values();
    }
}
