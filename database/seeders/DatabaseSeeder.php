<?php

namespace Database\Seeders;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\DrugPresentation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Activity::truncate();
        Confiscation::truncate();
        Ammunition::truncate();
        Weapon::truncate();
        AmmunitionConfiscation::truncate();
        DrugConfiscation::truncate();
        WeaponConfiscation::truncate();
        DrugPresentation::truncate();
        Drug::truncate();
        Permission::truncate();
        Role::truncate();
        User::truncate();

        User::factory()->create([
            'name' => 'cris',
            'email' => 'cris_itg@yahoo.es',
        ]);
        User::factory()->create([
            'name' => 'analista',
        ]);
        User::factory()->create([
            'name' => 'operador',
        ]);
        User::factory()->create([
            'name' => 'demoSwagger',
            'email' => 'demoSwagger@test.com',
        ]);
        Auth::attempt([
            'email' => 'cris_itg@yahoo.es',
            'password' => 'password',
        ]);

        foreach ([
            'frontend.user.view',
            'frontend.user.create',
            'frontend.user.update',
            'frontend.user.delete',
            'frontend.role.view',
            'frontend.role.create',
            'frontend.role.update',
            'frontend.role.delete',
            'frontend.permission.view',
            'frontend.permission.create',
            'frontend.permission.update',
            'frontend.permission.delete',
            'frontend.drug.view',
            'frontend.drug.create',
            'frontend.drug.update',
            'frontend.drug.delete',
            'frontend.drugPresentation.view',
            'frontend.drugPresentation.create',
            'frontend.drugPresentation.update',
            'frontend.drugPresentation.delete',
            'frontend.weapon.view',
            'frontend.weapon.create',
            'frontend.weapon.update',
            'frontend.weapon.delete',
            'frontend.ammunition.view',
            'frontend.ammunition.create',
            'frontend.ammunition.update',
            'frontend.ammunition.delete',
            'frontend.confiscation.view',
            'frontend.confiscation.create',
            'frontend.confiscation.update',
            'frontend.confiscation.delete',
            'frontend.drugConfiscation.view',
            'frontend.drugConfiscation.create',
            'frontend.drugConfiscation.update',
            'frontend.drugConfiscation.delete',
            'frontend.weaponConfiscation.view',
            'frontend.weaponConfiscation.create',
            'frontend.weaponConfiscation.update',
            'frontend.weaponConfiscation.delete',
            'frontend.ammunitionConfiscation.view',
            'frontend.ammunitionConfiscation.create',
            'frontend.ammunitionConfiscation.update',
            'frontend.ammunitionConfiscation.delete',
            'frontend.dashboard.view',
            'frontend.graph.view',
            'frontend.map.view',
            'frontend.activity.view',
        ] as $permissionName) {
            Permission::factory()->create(['name' => $permissionName]);
        }

        Permission::factory(25)->create();

        foreach ([
            'frontend.administrador',
            'frontend.operador',
            'frontend.analista',
        ] as $roleName) {
            Role::factory()->create(['name' => $roleName]);
        }

        Role::factory(4)->givePermissionsToRole()->create();
        User::factory(30)->giveRolesToUser()->create();
        Ammunition::factory(5)->create();
        Confiscation::factory(5000)->create();
        DrugPresentation::factory(5)->create();
        Drug::factory(5)->create();
        Weapon::factory(5)->create();
        AmmunitionConfiscation::factory(1000)->create();
        DrugConfiscation::factory(1000)->create();
        WeaponConfiscation::factory(1000)->create();

    }
}
