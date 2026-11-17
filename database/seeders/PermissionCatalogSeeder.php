<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        try {
            DB::transaction(function (): void {
                foreach (['user', 'role', 'permission', 'drug', 'drugPresentation', 'weapon', 'ammunition', 'confiscation', 'drugConfiscation', 'weaponConfiscation', 'ammunitionConfiscation'] as $resource) {
                    foreach (['view', 'create', 'update', 'delete'] as $action) {
                        $this->createPermission($resource.'.'.$action);
                    }
                }

                foreach (['dashboard.view', 'graph.view', 'map.view', 'activity.view'] as $permission) {
                    $this->createPermission($permission);
                }

                foreach (['administrador', 'operador', 'analista'] as $roleName) {
                    if (Role::onlyTrashed()->where('name', $roleName)->where('guard_name', 'api')->exists()) {
                        throw new DomainException('El rol '.$roleName.' está eliminado; resuelve su estado antes de inicializar permisos.');
                    }

                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
                }

                foreach (app(RolePermissionsSeeder::class)->permissionsByRole() as $roleName => $permissions) {
                    Role::findByName($roleName, 'api')->givePermissionTo($permissions);
                }
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }

    private function createPermission(string $permissionName): void
    {
        if (Permission::onlyTrashed()->where('name', $permissionName)->where('guard_name', 'api')->exists()) {
            throw new DomainException('El permiso '.$permissionName.' está eliminado; resuelve su estado antes de inicializar permisos.');
        }

        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
    }
}
