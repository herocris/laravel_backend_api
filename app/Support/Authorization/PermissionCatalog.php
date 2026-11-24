<?php

namespace App\Support\Authorization;

class PermissionCatalog
{
    /** @return list<string> */
    public static function names(): array
    {
        $permissions = [];

        foreach (['user', 'role', 'permission', 'drug', 'drugPresentation', 'weapon', 'ammunition', 'confiscation', 'drugConfiscation', 'weaponConfiscation', 'ammunitionConfiscation'] as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = $resource.'.'.$action;
            }
        }

        return [...$permissions, 'dashboard.view', 'graph.view', 'map.view', 'activity.view'];
    }

    public static function contains(string $name): bool
    {
        return in_array($name, self::names(), true);
    }
}
