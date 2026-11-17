<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->permissionsByRole() as $roleName => $permissions) {
                Role::findByName($roleName, 'api')->syncPermissions($permissions);
            }
        });
    }

    /**
     * @return array<string, list<string>>
     */
    public function permissionsByRole(): array
    {
        $readPermissions = [
            'drug.view',
            'drugPresentation.view',
            'weapon.view',
            'ammunition.view',
            'confiscation.view',
            'drugConfiscation.view',
            'weaponConfiscation.view',
            'ammunitionConfiscation.view',
            'dashboard.view',
            'graph.view',
            'map.view',
        ];

        return [
            'administrador' => Permission::where('guard_name', 'api')->pluck('name')->all(),
            'operador' => [
                ...$readPermissions,
                'drug.create',
                'drug.update',
                'drugPresentation.create',
                'drugPresentation.update',
                'weapon.create',
                'weapon.update',
                'ammunition.create',
                'ammunition.update',
                'confiscation.create',
                'confiscation.update',
                'drugConfiscation.create',
                'drugConfiscation.update',
                'weaponConfiscation.create',
                'weaponConfiscation.update',
                'ammunitionConfiscation.create',
                'ammunitionConfiscation.update',
            ],
            'analista' => $readPermissions,
        ];
    }
}
