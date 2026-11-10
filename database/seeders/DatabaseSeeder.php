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
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',
            'drug.view',
            'drug.create',
            'drug.update',
            'drug.delete',
            'drugPresentation.view',
            'drugPresentation.create',
            'drugPresentation.update',
            'drugPresentation.delete',
            'weapon.view',
            'weapon.create',
            'weapon.update',
            'weapon.delete',
            'ammunition.view',
            'ammunition.create',
            'ammunition.update',
            'ammunition.delete',
            'confiscation.view',
            'confiscation.create',
            'confiscation.update',
            'confiscation.delete',
            'drugConfiscation.view',
            'drugConfiscation.create',
            'drugConfiscation.update',
            'drugConfiscation.delete',
            'weaponConfiscation.view',
            'weaponConfiscation.create',
            'weaponConfiscation.update',
            'weaponConfiscation.delete',
            'ammunitionConfiscation.view',
            'ammunitionConfiscation.create',
            'ammunitionConfiscation.update',
            'ammunitionConfiscation.delete',
            'dashboard.view',
            'graph.view',
            'map.view',
            'activity.view',
        ] as $permissionName) {
            Permission::factory()->create(['name' => $permissionName]);
        }

        Permission::factory(25)->create();

        foreach ([
            'administrador',
            'operador',
            'analista',
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

        //$this->call(ConfiscationGraphViewsSeeder::class);

    }
}
