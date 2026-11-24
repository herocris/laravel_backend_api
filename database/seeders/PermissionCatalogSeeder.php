<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\AdministrativeTransaction;
use App\Support\Authorization\PermissionCatalog;
use DomainException;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionCatalogSeeder extends Seeder
{
    public function __construct(private AdministrativeTransaction $transaction) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        try {
            $this->transaction->run(function (): void {
                foreach (PermissionCatalog::names() as $permission) {
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
