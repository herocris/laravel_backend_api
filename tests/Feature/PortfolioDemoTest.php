<?php

namespace Tests\Feature;

use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\User;
use Database\Seeders\PortfolioDemoSeeder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioDemoTest extends TestCase
{
    public function test_prepares_only_synthetic_data_without_files_or_fixed_passwords(): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->seed(PortfolioDemoSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('permissions', 48);
        $this->assertDatabaseCount('confiscations', 3);
        $this->assertDatabaseCount('drug_confiscations', 3);
        $this->assertDatabaseCount('weapon_confiscations', 3);
        $this->assertDatabaseCount('ammunition_confiscations', 3);
        $this->assertSame(['administrador@demo.invalid', 'operador@demo.invalid', 'analista@demo.invalid'], User::orderBy('id')->pluck('email')->all());
        $this->assertSame(3, User::distinct()->count('password'));
        $this->assertDatabaseHas('activity_log', ['log_name' => 'Sistema', 'causer_id' => null, 'event' => 'created']);
        Storage::disk('public')->assertDirectoryEmpty('/');
        Storage::disk('evidence')->assertDirectoryEmpty('/');
    }

    public function test_analyst_can_read_demo_and_map_but_cannot_write_or_read_audit(): void
    {
        $this->seed(PortfolioDemoSeeder::class);
        $this->actingAs(User::where('email', 'analista@demo.invalid')->firstOrFail(), 'api');

        $this->getJson('/api/confiscation?per_page=2')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('total', 3);
        $this->getJson('/api/confiscation/map?start_date=2026-01-01&end_date=2026-12-31&drugs=%5B1%5D')
            ->assertOk()->assertJsonCount(3, 'mapItems');
        $this->getJson('/api/drugGraphIndex?period=quarter&typeGraph=bar&start_date=2026-01-01&end_date=2026-12-31&drugs=%5B1%5D')
            ->assertOk()->assertJsonPath('lineBarData.0.name', '2026-Q1')->assertJsonPath('pieData.0.value', 6);
        $this->postJson('/api/confiscation', $this->confiscationInput())->assertForbidden();
        $this->getJson('/api/activity')->assertForbidden();
        $this->getJson('/api/user')->assertForbidden();
        $this->assertDatabaseCount('confiscations', 3);
    }

    public function test_operator_can_write_and_administrator_can_audit_the_actor(): void
    {
        $this->seed(PortfolioDemoSeeder::class);
        $operator = User::where('email', 'operador@demo.invalid')->firstOrFail();
        $this->actingAs($operator, 'api');
        $this->postJson('/api/confiscation', $this->confiscationInput())
            ->assertOk()->assertJsonPath('observacion', 'DEMO creada por operador');
        $record = Confiscation::where('observation', 'DEMO creada por operador')->firstOrFail();
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Confiscation::class, 'subject_id' => $record->id,
            'causer_id' => $operator->id, 'event' => 'created',
        ]);
        $this->deleteJson('/api/confiscation/'.$record->id)->assertForbidden();
        $this->getJson('/api/activity')->assertForbidden();
        $this->actingAs(User::where('email', 'administrador@demo.invalid')->firstOrFail(), 'api');
        $this->getJson('/api/activity?tipo_de_evento=created&sort_by=identificador&type=desc&per_page=2')
            ->assertOk()->assertJsonPath('data.0.id_usuario', $operator->id);
        $this->assertDatabaseCount('confiscations', 4);
    }

    public function test_rejects_repeated_seeding_without_changing_data_or_passwords(): void
    {
        $this->seed(PortfolioDemoSeeder::class);
        $passwords = User::pluck('password')->all();
        $auditCount = DB::table('activity_log')->count();
        try {
            $this->seed(PortfolioDemoSeeder::class);
            $this->fail('Debe rechazar una base ocupada.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('tablas vacías', $exception->getMessage());
        }
        $this->assertSame($passwords, User::pluck('password')->all());
        $this->assertDatabaseCount('confiscations', 3);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_rejects_existing_business_data_even_without_users(): void
    {
        $drug = Drug::factory()->create();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tablas vacías');
        try {
            $this->seed(PortfolioDemoSeeder::class);
        } finally {
            $this->assertDatabaseHas('drugs', ['id' => $drug->id, 'description' => $drug->description]);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('permissions', 0);
        }
    }

    public static function unsafeEnvironments(): array
    {
        return ['production' => ['production'], 'staging' => ['staging'], 'local memory' => ['local'], 'demo memory' => ['demo']];
    }

    #[DataProvider('unsafeEnvironments')]
    public function test_rejects_environments_without_the_designated_demo_file(string $environment): void
    {
        $this->app['env'] = $environment;
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('SQLite aislada');
        try {
            app(PortfolioDemoSeeder::class)->run();
        } finally {
            $this->app['env'] = 'testing';
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('permissions', 0);
        }
    }

    public static function unsafeConnections(): array
    {
        return [
            'otro archivo SQLite' => ['sqlite', '/not-a-demo.sqlite'],
            'MySQL' => ['mysql', ':memory:'],
        ];
    }

    #[DataProvider('unsafeConnections')]
    public function test_rejects_other_connections_before_opening_them(string $driver, string $database): void
    {
        config(['database.connections.unsafe_demo' => ['driver' => $driver, 'database' => $database], 'database.default' => 'unsafe_demo']);
        $this->expectException(DomainException::class);
        try {
            $this->seed(PortfolioDemoSeeder::class);
        } finally {
            config(['database.default' => 'sqlite']);
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_rejects_an_external_audit_connection(): void
    {
        config(['activitylog.database_connection' => 'mysql']);
        $this->expectException(DomainException::class);
        try {
            $this->seed(PortfolioDemoSeeder::class);
        } finally {
            config(['activitylog.database_connection' => null]);
            $this->assertDatabaseCount('users', 0);
        }
    }

    /** @return array<string, int|float|string> */
    private function confiscationInput(): array
    {
        return [
            'fecha' => '2026-10-15', 'observacion' => 'DEMO creada por operador',
            'direccion' => 'Dirección ficticia', 'departamento' => 'Región DEMO',
            'municipalidad' => 'Municipio ficticio', 'latitud' => 14.2, 'longitud' => -87.2,
        ];
    }
}
