<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Permission::truncate();
        Role::truncate();
        User::truncate();

        Permission::factory(25)->create();
        Role::factory(4)->givePermissionsToRole()->create();
        User::factory(5)->giveRolesToUser()->create();

        User::factory()->create([
            'name' => 'cris',
            'email' => 'cris_itg@yahoo.es',
        ]);
    }
}
