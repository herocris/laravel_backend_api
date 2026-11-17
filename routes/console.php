<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('authorization:assign-administrator {user : ID exacto del usuario designado}', function (string $user): int {
    if (! ctype_digit($user)) {
        $this->error('Indica el ID numérico exacto de un usuario activo.');

        return Command::FAILURE;
    }

    return DB::transaction(function () use ($user): int {
        $administrator = User::whereKey($user)->lockForUpdate()->first();
        if ($administrator === null) {
            $this->error('No existe un usuario activo con ese ID.');

            return Command::FAILURE;
        }

        $role = Role::where('name', 'administrador')->where('guard_name', 'api')->first();
        if ($role === null || ! $role->permissions()->exists()) {
            $this->error('Inicializa antes el catálogo con PermissionCatalogSeeder.');

            return Command::FAILURE;
        }

        $administrator->assignRole($role);
        $this->info('Rol administrador (api) asignado al usuario ID '.$administrator->id.'.');

        return Command::SUCCESS;
    });
})->purpose('Asignar el rol administrador api a un usuario existente designado explícitamente');
