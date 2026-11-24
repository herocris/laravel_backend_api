<?php

namespace Tests\Feature;

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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpInputContractTest extends TestCase
{
    public static function internalNames(): array
    {
        $cases = [];

        foreach ([
            'user' => [User::class, 'nombre', 'name'],
            'role' => [Role::class, 'nombre', 'name'],
            'permission' => [Permission::class, 'nombre', 'name'],
            'drug' => [Drug::class, 'descripcion', 'description'],
            'drugPresentation' => [DrugPresentation::class, 'descripcion', 'description'],
            'weapon' => [Weapon::class, 'descripcion', 'description'],
            'ammunition' => [Ammunition::class, 'descripcion', 'description'],
            'confiscation' => [Confiscation::class, 'fecha', 'date'],
            'drugConfiscation' => [DrugConfiscation::class, 'cantidad', 'amount'],
            'weaponConfiscation' => [WeaponConfiscation::class, 'cantidad', 'amount'],
            'ammunitionConfiscation' => [AmmunitionConfiscation::class, 'cantidad', 'amount'],
            'user permissions' => [User::class, 'permisos', 'permissions'],
            'role permissions' => [Role::class, 'permisos', 'permissions'],
        ] as $label => [$model, $external, $internal]) {
            $resource = explode(' ', $label)[0];

            foreach (['POST', 'PUT'] as $method) {
                foreach (['internal only', 'external first', 'internal first'] as $order) {
                    $cases[$label.' '.$method.' '.$order] = [$resource, $model, $external, $internal, $method, $order];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('internalNames')]
    public function test_internal_names_return_422_without_writes_regardless_of_order(
        string $resource,
        string $model,
        string $external,
        string $internal,
        string $method,
        string $order,
    ): void {
        Storage::fake('public');
        Storage::fake('evidence');
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.'.'.($method === 'POST' ? 'create' : 'update')]);
        $target = $this->createRecord($model);
        $before = $target->fresh()->getRawOriginal();
        $count = $model::count();
        $auditCount = Model::resolveConnection()->table('activity_log')->count();
        $payload = match ($order) {
            'internal only' => [$internal => 'internal'],
            'external first' => [$external => 'external', $internal => 'internal'],
            'internal first' => [$internal => 'internal', $external => 'external'],
        };

        $this->actingAs($actor, 'api')
            ->json($method, '/api/'.$resource.($method === 'PUT' ? '/'.$target->id : ''), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('error.'.$external, fn (array $messages): bool => in_array("Utilice el campo {$external}; no se admite su nombre interno.", $messages, true));

        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount($target->getTable(), $count);
        $this->assertDatabaseCount('activity_log', $auditCount);
        Storage::disk('public')->assertEmpty();
        Storage::disk('evidence')->assertEmpty();
    }

    private function createRecord(string $model): Model
    {
        if (in_array($model, [DrugConfiscation::class, WeaponConfiscation::class, AmmunitionConfiscation::class], true)) {
            Confiscation::factory()->create();

            match ($model) {
                DrugConfiscation::class => [
                    Drug::factory()->create(),
                    DrugPresentation::factory()->create(),
                ],
                WeaponConfiscation::class => Weapon::factory()->create(),
                AmmunitionConfiscation::class => Ammunition::factory()->create(),
            };
        }

        return $model::factory()->create();
    }

    public function test_unknown_fields_are_discarded_and_public_user_contract_is_preserved(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.create']);
        $permission = Permission::findOrCreate('drug.view', 'api');
        $actor->givePermissionTo($permission);

        $response = $this->actingAs($actor, 'api')->postJson('/api/user', [
            'nombre' => 'Public User',
            'correo' => 'public@example.test',
            'password' => 'secret123',
            'permisos' => [$permission->id],
            'roles' => [],
            'identificador' => 999999,
            'guard_name' => 'web',
            'is_admin' => true,
            'email_verified_at' => '2024-01-01',
            'unexpected' => ['nombre' => 'Overwrite'],
        ])->assertOk();

        $created = User::where('email', 'public@example.test')->firstOrFail();
        $response->assertExactJson([
            'identificador' => $created->id,
            'nombre' => 'Public User',
            'correo' => 'public@example.test',
            'roles' => [],
            'permisos' => [$permission->id],
        ]);
        $this->assertDatabaseHas('users', ['id' => $created->id, 'name' => 'Public User', 'email_verified_at' => null]);
        $this->assertDatabaseMissing('users', ['id' => 999999]);
        $this->assertSame([$permission->id], $created->permissions->modelKeys());
        $this->assertSame([], $created->roles->modelKeys());
    }

    public function test_query_internal_alias_returns_422_before_saving_a_valid_body(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['permission.create']);
        $count = Permission::count();
        $auditCount = Model::resolveConnection()->table('activity_log')->count();

        $this->actingAs($actor, 'api')->postJson('/api/permission?name=internal', ['nombre' => 'public'])
            ->assertUnprocessable()
            ->assertExactJson([
                'error' => ['nombre' => ['Utilice el campo nombre; no se admite su nombre interno.']],
                'code' => 422,
            ]);

        $this->assertDatabaseCount('permissions', $count);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_nested_privilege_error_uses_public_name_and_concrete_index(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.create']);
        $permission = Permission::findOrCreate('drug.view', 'api');
        $auditCount = Model::resolveConnection()->table('activity_log')->count();

        $this->actingAs($actor, 'api')->postJson('/api/user', [
            'nombre' => 'Invalid',
            'correo' => 'invalid@example.test',
            'password' => 'secret123',
            'permisos' => [$permission->id, 'invalid'],
        ])->assertUnprocessable()->assertExactJson([
            'error' => ['permisos.1' => ['The permisos.1 field must be an integer.']],
            'code' => 422,
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
        $this->assertDatabaseCount('model_has_permissions', 1);
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public static function photoNames(): array
    {
        return ['public first' => [false], 'internal first' => [true]];
    }

    #[DataProvider('photoNames')]
    public function test_conflicting_upload_names_return_422_without_files_or_updates(bool $internalFirst): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $record = $this->createRecord(DrugConfiscation::class);
        $before = $record->fresh()->getRawOriginal();
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drugConfiscation.update']);
        $auditCount = Model::resolveConnection()->table('activity_log')->count();
        $files = ['foto' => UploadedFile::fake()->image('public.png'), 'photo' => UploadedFile::fake()->image('internal.png')];

        $this->actingAs($actor, 'api')->putJson('/api/drugConfiscation/'.$record->id, [
            'cantidad' => 2,
            'peso' => 3,
            'decomiso' => $record->confiscation_id,
            'droga' => $record->drug_id,
            'presentacion' => $record->drug_presentation_id,
            ...($internalFirst ? array_reverse($files, true) : $files),
        ])->assertUnprocessable()->assertExactJson([
            'error' => ['foto' => ['Utilice el campo foto; no se admite su nombre interno.']],
            'code' => 422,
        ]);

        $this->assertSame($before, $record->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_log', $auditCount);
        Storage::disk('public')->assertEmpty();
        Storage::disk('evidence')->assertEmpty();
    }
}
