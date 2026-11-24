<?php

namespace Database\Seeders;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\DrugPresentation;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use App\Support\Authorization\AdministrativeTransaction;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortfolioDemoSeeder extends Seeder
{
    public function __construct(private AdministrativeTransaction $transaction) {}

    public function run(): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $isolated = app()->environment('testing') && $database === ':memory:';
        $isolated = $isolated || (app()->environment('local', 'demo')
            && realpath($database) !== false
            && realpath($database) === realpath(database_path('portfolio-demo.sqlite')));

        if (! $isolated || $connection->getDriverName() !== 'sqlite'
            || config('activitylog.database_connection') !== null) {
            throw new DomainException('La demo exige SQLite aislada: portfolio-demo.sqlite en local o :memory: en testing; auditoría en la conexión por defecto.');
        }

        $credentials = $this->transaction->run(function (): array {
            foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'confiscations', 'drugs', 'drug_presentations', 'weapons', 'ammunitions', 'drug_confiscations', 'weapon_confiscations', 'ammunition_confiscations', 'activity_log'] as $table) {
                if (DB::table($table)->exists()) {
                    throw new DomainException('La demo requiere tablas vacías; no modifica ni reinicia datos existentes.');
                }
            }

            $this->call(PermissionCatalogSeeder::class);
            $credentials = [];
            foreach (['administrador', 'operador', 'analista'] as $role) {
                $password = Str::password(24);
                $email = $role.'@demo.invalid';
                $user = User::create(['name' => 'Demo '.$role, 'email' => $email, 'password' => $password]);
                $user->assignRole($role);
                $credentials[] = [$role, $email, $password];
            }

            $drug = Drug::create(['description' => 'Sustancia sintética DEMO', 'logo' => '']);
            $presentation = DrugPresentation::create(['description' => 'Envase ficticio DEMO', 'logo' => '']);
            $weapon = Weapon::create(['description' => 'Objeto inerte DEMO', 'logo' => '']);
            $ammunition = Ammunition::create(['description' => 'Unidad inerte DEMO', 'logo' => '']);

            foreach (['2026-01-15', '2026-04-15', '2026-07-15'] as $index => $date) {
                $confiscation = Confiscation::create([
                    'date' => $date,
                    'observation' => 'DEMO sintética '.($index + 1),
                    'direction' => 'Dirección ficticia '.($index + 1),
                    'department' => 'Región DEMO',
                    'municipality' => 'Municipio ficticio',
                    'latitude' => 14.0 + $index / 10,
                    'length' => -87.0 - $index / 10,
                ]);
                DrugConfiscation::create([
                    'confiscation_id' => $confiscation->id, 'drug_id' => $drug->id,
                    'drug_presentation_id' => $presentation->id,
                    'amount' => $index + 1, 'weight' => 12.5 * ($index + 1), 'photo' => '',
                ]);
                WeaponConfiscation::create([
                    'confiscation_id' => $confiscation->id, 'weapon_id' => $weapon->id,
                    'amount' => $index + 1, 'photo' => '',
                ]);
                AmmunitionConfiscation::create([
                    'confiscation_id' => $confiscation->id, 'ammunition_id' => $ammunition->id,
                    'amount' => 10 * ($index + 1), 'photo' => '',
                ]);
            }

            return $credentials;
        });

        $this->command?->table(['Rol local', 'Correo sintético', 'Contraseña efímera'], $credentials);
        $this->command?->warn('Demo local: no publicar estas cuentas. Una segunda ejecución se rechaza; conserva la base para revisar el recorrido.');
    }
}
