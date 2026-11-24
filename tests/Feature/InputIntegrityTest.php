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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InputIntegrityTest extends TestCase
{
    #[DataProvider('invalidConfiscationInputs')]
    public function test_invalid_confiscation_returns_422_without_writing(string $method, string $field, mixed $value, string $message): void
    {
        $this->authenticateFor('confiscation.'.($method === 'POST' ? 'create' : 'update'));
        $target = Confiscation::factory()->create();
        $before = $target->fresh()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();

        $this->json($method, '/api/confiscation'.($method === 'PUT' ? '/'.$target->id : ''), array_replace($this->confiscationPayload(), [$field => $value]))
            ->assertUnprocessable()->assertJsonPath('code', 422)->assertJsonPath('error.'.$field.'.0', $message);

        $this->assertDatabaseCount('confiscations', 1);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public static function invalidConfiscationInputs(): array
    {
        $cases = [
            'invalid calendar date' => ['fecha', '2026-02-30', 'The fecha field must match the format Y-m-d.'],
            'date with time' => ['fecha', '2026-01-01 12:00:00', 'The fecha field must match the format Y-m-d.'],
            'date below mysql range' => ['fecha', '0999-12-31', 'The fecha field must be a date after or equal to 1000-01-01.'],
            'latitude type' => ['latitud', ['bad'], 'The latitud field must be a number.'],
            'latitude low' => ['latitud', -90.000001, 'The latitud field must be between -90 and 90.'],
            'latitude high' => ['latitud', 90.000001, 'The latitud field must be between -90 and 90.'],
            'longitude low' => ['longitud', -180.000001, 'The longitud field must be between -180 and 180.'],
            'longitude high' => ['longitud', 180.000001, 'The longitud field must be between -180 and 180.'],
        ];
        foreach (['observacion', 'direccion', 'departamento', 'municipalidad'] as $field) {
            $cases[$field.' type'] = [$field, ['bad'], 'The '.$field.' field must be a string.'];
            $cases[$field.' length'] = [$field, str_repeat('a', 256), 'The '.$field.' field must not be greater than 255 characters.'];
        }
        $result = [];
        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => $case) {
                $result[$method.' '.$name] = [$method, ...$case];
            }
        }

        return $result;
    }

    #[TestWith([-90, -180])]
    #[TestWith([90, 180])]
    public function test_geographic_and_text_boundaries_are_saved(int $latitude, int $longitude): void
    {
        $this->authenticateFor('confiscation.create');
        $payload = array_replace($this->confiscationPayload(), [
            'latitud' => $latitude, 'longitud' => $longitude, 'observacion' => str_repeat('á', 255),
            'identificador' => 99999, 'deleted_at' => '2026-01-01',
        ]);

        $response = $this->postJson('/api/confiscation', $payload)->assertCreated()->assertJsonPath('data.fecha', '2024-02-29');

        $this->assertDatabaseHas('confiscations', [
            'id' => $response->json('data.identificador'), 'latitude' => $latitude, 'length' => $longitude,
            'observation' => str_repeat('á', 255), 'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('confiscations', ['id' => 99999]);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filter_returns_422(string $endpoint, string $field, mixed $value, string $errorField, string $message): void
    {
        $this->authenticateFor($endpoint === 'confiscation/map' ? 'map.view' : 'graph.view');
        $payload = ['start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'period' => 'month', 'typeGraph' => 'bar',
            'drugs' => '[]', 'weapons' => '[]', 'ammunitions' => '[]'];

        $this->getJson('/api/'.$endpoint.'?'.http_build_query(array_replace($payload, [$field => $value])))
            ->assertUnprocessable()->assertJsonPath('code', 422)->assertJson(['error' => [$errorField => [$message]]]);
    }

    public static function invalidFilters(): array
    {
        $result = [];
        foreach (['drugs' => 'drugGraphIndex', 'weapons' => 'weaponGraphIndex', 'ammunitions' => 'ammunitionGraphIndex'] as $field => $endpoint) {
            foreach ([$endpoint, 'confiscation/map'] as $route) {
                foreach (['malformed JSON' => '[1,', 'JSON object' => '{}', 'scalar JSON' => '1', 'JSON null' => 'null'] as $name => $value) {
                    $result[$route.$field.$name] = [$route, $field, $value, $field, 'The '.$field.' field must be an array.'];
                }
                $result[$route.$field.' duplicate'] = [$route, $field, '[1,"1"]', $field.'.0', 'The '.$field.'.0 field has a duplicate value.'];
                $result[$route.$field.' empty element'] = [$route, $field, '[null]', $field.'.0', 'The '.$field.'.0 field is required.'];
                $result[$route.$field.' nested'] = [$route, $field, '[[1]]', $field.'.0', 'The '.$field.'.0 field must be an integer.'];
                $result[$route.$field.' missing id'] = [$route, $field, '[999999]', $field.'.0', 'The selected '.$field.'.0 is invalid.'];
                $result[$route.$field.' non integer'] = [$route, $field, '["oops"]', $field.'.0', 'The '.$field.'.0 field must be an integer.'];
                $result[$route.$field.' object keys'] = [$route, $field, ['bad' => 1], $field, 'The '.$field.' field must be a list.'];
            }
            $result[$endpoint.' reversed range'] = [$endpoint, 'end_date', '2025-12-31', 'end_date', 'The end date field must be a date after or equal to start date.'];
        }
        $result['map reversed range'] = ['confiscation/map', 'end_date', '2025-12-31', 'end_date', 'The end date field must be a date after or equal to start date.'];

        return $result;
    }

    #[TestWith(['drug', 'drugs', Drug::class])]
    #[TestWith(['weapon', 'weapons', Weapon::class])]
    #[TestWith(['ammunition', 'ammunitions', Ammunition::class])]
    public function test_graph_accepts_json_and_native_lists_with_historical_catalogs(string $resource, string $field, string $model): void
    {
        $this->authenticateFor('graph.view');
        $catalog = $model::factory()->create();
        $catalog->delete();
        $payload = ['start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'period' => 'month', 'typeGraph' => 'bar'];

        $this->getJson('/api/'.$resource.'GraphIndex?'.http_build_query($payload + [$field => json_encode([(string) $catalog->id])]))
            ->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->getJson('/api/'.$resource.'GraphIndex?'.http_build_query($payload + [$field => [$catalog->id]]))
            ->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
    }

    #[DataProvider('invalidItemValues')]
    public function test_invalid_item_returns_422_without_writing(string $resource, string $model, string $method, string $field, mixed $value, string $message): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->authenticateFor($resource.'.'.($method === 'POST' ? 'create' : 'update'));
        $target = $this->createItem($model);
        $before = $target->fresh()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();
        $payload = array_replace($this->itemPayload($target), [$field => $value, 'foto' => UploadedFile::fake()->image('photo.png')]);

        $this->json($method, '/api/'.$resource.($method === 'PUT' ? '/'.$target->id : ''), $payload)
            ->assertUnprocessable()->assertJsonPath('error.'.$field.'.0', $message);

        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount($target->getTable(), 1);
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function invalidItemValues(): array
    {
        $result = [];
        foreach (self::itemModels() as $resource => $model) {
            $cases = [
                ['cantidad', 0, 'The cantidad field must be at least 1.'],
                ['cantidad', -1, 'The cantidad field must be at least 1.'],
                ['cantidad', 2147483648, 'The cantidad field must not be greater than 2147483647.'],
                ['cantidad', 1.5, 'The cantidad field must be an integer.'],
                ['decomiso', ['bad'], 'The decomiso field must be an integer.'],
            ];
            if ($resource === 'drugConfiscation') {
                $cases = [...$cases,
                    ['peso', 0, 'The peso field must be greater than 0.'],
                    ['peso', -1, 'The peso field must be greater than 0.'],
                    ['peso', '12.345', 'The peso field must have 0-2 decimal places.'],
                    ['peso', '1e3', 'The peso field must have 0-2 decimal places.'],
                    ['peso', 'abc', 'The peso field must be a number.'],
                    ['peso', str_repeat('9', 400), 'The peso field must not be greater than 1.7976931348623E+308.'],
                ];
            }
            foreach (['POST', 'PUT'] as $method) {
                foreach ($cases as $index => $case) {
                    $result[$resource.$method.$index] = [$resource, $model, $method, ...$case];
                }
            }
        }

        return $result;
    }

    #[DataProvider('deletedAssociations')]
    public function test_deleted_association_returns_422_even_if_unchanged(string $resource, string $model, string $method, string $field, string $relation): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->authenticateFor($resource.'.'.($method === 'POST' ? 'create' : 'update'));
        $target = $this->createItem($model);
        $target->{$relation}->delete();
        $before = $target->fresh()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();

        $this->json($method, '/api/'.$resource.($method === 'PUT' ? '/'.$target->id : ''),
            $this->itemPayload($target) + ['foto' => UploadedFile::fake()->image('photo.png')])
            ->assertUnprocessable()->assertJsonPath('error.'.$field.'.0', match ($field) {
                'decomiso' => 'The selected decomiso is invalid.',
                'droga' => 'The selected droga is invalid.',
                'presentacion' => 'The selected presentacion is invalid.',
                'arma' => 'The selected arma is invalid.',
                'municion' => 'The selected municion is invalid.',
            });

        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount($target->getTable(), 1);
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function deletedAssociations(): array
    {
        $result = [];
        foreach (self::itemModels() as $resource => $model) {
            $relations = ['decomiso' => 'confiscation'] + match ($resource) {
                'drugConfiscation' => ['droga' => 'drug', 'presentacion' => 'drugPresentation'],
                'weaponConfiscation' => ['arma' => 'weapon'],
                'ammunitionConfiscation' => ['municion' => 'ammunition'],
            };
            foreach (['POST', 'PUT'] as $method) {
                foreach ($relations as $field => $relation) {
                    $result[$resource.$method.$field] = [$resource, $model, $method, $field, $relation];
                }
            }
        }

        return $result;
    }

    #[DataProvider('itemModelsForTests')]
    public function test_item_remains_readable_after_parent_and_catalog_are_deleted(string $resource, string $model): void
    {
        $this->authenticateFor($resource.'.view');
        $target = $this->createItem($model);
        $target->confiscation->delete();
        $relation = match ($resource) {
            'drugConfiscation' => 'drug', 'weaponConfiscation' => 'weapon', 'ammunitionConfiscation' => 'ammunition',
        };
        $target->{$relation}->delete();

        $this->getJson('/api/'.$resource.'/'.$target->id)->assertOk()
            ->assertJsonPath('data.decomiso.identificador', $target->confiscation_id);
    }

    #[DataProvider('itemModelsForTests')]
    public function test_positive_item_boundaries_and_numeric_strings_are_saved(string $resource, string $model): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->authenticateFor($resource.'.create');
        $target = $this->createItem($model);
        $payload = array_replace($this->itemPayload($target), ['cantidad' => '2147483647', 'peso' => '0.01',
            'foto' => UploadedFile::fake()->image('photo.png')]);

        $response = $this->postJson('/api/'.$resource, $payload)->assertCreated()->assertJsonPath('data.cantidad', 2147483647);

        $this->assertDatabaseHas($target->getTable(), ['id' => $response->json('data.identificador'), 'amount' => 2147483647]);
        Storage::disk('evidence')->assertExists($response->json('data.foto'));
    }

    #[TestWith(['POST'])]
    #[TestWith(['PUT'])]
    public function test_weight_in_grams_is_saved_without_rounding(string $method): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->authenticateFor('drugConfiscation.'.($method === 'POST' ? 'create' : 'update'));
        $target = $this->createItem(DrugConfiscation::class);
        $payload = array_replace($this->itemPayload($target), ['cantidad' => '1', 'peso' => '123.45']);
        if ($method === 'POST') {
            $payload['foto'] = UploadedFile::fake()->image('photo.png');
        }

        $response = $this->json($method, '/api/drugConfiscation'.($method === 'PUT' ? '/'.$target->id : ''), $payload)
            ->assertStatus($method === 'POST' ? 201 : 200)->assertJsonPath('data.cantidad', 1)->assertJsonPath('data.peso', 123.45);

        $this->assertDatabaseHas('drug_confiscations', ['id' => $response->json('data.identificador'), 'weight' => 123.45]);
    }

    #[DataProvider('itemModelsForTests')]
    public function test_map_accepts_json_and_native_lists_and_returns_selected_items(string $resource, string $model): void
    {
        $this->authenticateFor('map.view');
        $parent = Confiscation::factory()->create(['date' => '2026-01-01']);
        $target = $this->createItem($model, $parent);
        $field = match ($resource) {
            'drugConfiscation' => 'drugs', 'weaponConfiscation' => 'weapons', 'ammunitionConfiscation' => 'ammunitions',
        };
        $catalogId = $target->{match ($resource) {
            'drugConfiscation' => 'drug_id', 'weaponConfiscation' => 'weapon_id', 'ammunitionConfiscation' => 'ammunition_id',
        }};
        $payload = ['start_date' => '2026-01-01', 'end_date' => '2026-01-01'];

        $json = $this->getJson('/api/confiscation/map?'.http_build_query($payload + [$field => json_encode([$catalogId])]))
            ->assertOk()->assertJsonPath('mapItems.0.id', $parent->id);
        $this->getJson('/api/confiscation/map?'.http_build_query($payload + [$field => [(string) $catalogId]]))
            ->assertOk()->assertExactJson($json->json());
    }

    public static function itemModelsForTests(): array
    {
        $result = [];
        foreach (self::itemModels() as $resource => $model) {
            $result[$resource] = [$resource, $model];
        }

        return $result;
    }

    private static function itemModels(): array
    {
        return ['drugConfiscation' => DrugConfiscation::class, 'weaponConfiscation' => WeaponConfiscation::class,
            'ammunitionConfiscation' => AmmunitionConfiscation::class];
    }

    /** @param class-string<DrugConfiscation|WeaponConfiscation|AmmunitionConfiscation> $model */
    private function createItem(string $model, ?Confiscation $parent = null): DrugConfiscation|WeaponConfiscation|AmmunitionConfiscation
    {
        $parent ??= Confiscation::factory()->create();
        match ($model) {
            DrugConfiscation::class => [Drug::factory()->create(), DrugPresentation::factory()->create()],
            WeaponConfiscation::class => Weapon::factory()->create(),
            AmmunitionConfiscation::class => Ammunition::factory()->create(),
        };

        return $model::factory()->create(['confiscation_id' => $parent->id, 'amount' => 1]);
    }

    private function itemPayload(DrugConfiscation|WeaponConfiscation|AmmunitionConfiscation $target): array
    {
        return ['cantidad' => 1, 'peso' => 1.25, 'decomiso' => $target->confiscation_id] + match (true) {
            $target instanceof DrugConfiscation => ['droga' => $target->drug_id, 'presentacion' => $target->drug_presentation_id],
            $target instanceof WeaponConfiscation => ['arma' => $target->weapon_id],
            $target instanceof AmmunitionConfiscation => ['municion' => $target->ammunition_id],
        };
    }

    #[DataProvider('textBoundaries')]
    public function test_overlong_name_or_description_returns_422_without_writing(string $resource, string $model, string $field, string $method): void
    {
        Storage::fake('public');
        Storage::fake('evidence');
        $this->authenticateFor($resource.'.'.($method === 'POST' ? 'create' : 'update'));
        $target = $model::factory()->create();
        $before = $target->fresh()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();
        $count = $model::count();
        $payload = [$field => str_repeat('a', 256), 'correo' => 'new@example.test', 'password' => 'password123',
            'logo' => UploadedFile::fake()->image('logo.png')];

        $this->json($method, '/api/'.$resource.($method === 'PUT' ? '/'.$target->id : ''), $payload)
            ->assertUnprocessable()->assertJsonPath('error.'.$field.'.0', 'The '.$field.' field must not be greater than 255 characters.');

        $this->assertDatabaseCount($target->getTable(), $count);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_log', $auditCount);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function textBoundaries(): array
    {
        $result = [];
        foreach (['drug' => Drug::class, 'drugPresentation' => DrugPresentation::class, 'weapon' => Weapon::class,
            'ammunition' => Ammunition::class, 'user' => User::class, 'role' => Role::class, 'permission' => Permission::class] as $resource => $model) {
            foreach (['POST', 'PUT'] as $method) {
                $result[$resource.$method] = [$resource, $model, in_array($resource, ['user', 'role', 'permission']) ? 'nombre' : 'descripcion', $method];
            }
        }

        return $result;
    }

    #[TestWith(['role', Role::class])]
    #[TestWith(['permission', Permission::class])]
    public function test_duplicate_name_update_returns_422_and_keeps_existing_record(string $resource, string $model): void
    {
        $this->authenticateFor($resource.'.update');
        $target = $model::factory()->create();
        $other = $model::factory()->create();
        $before = $target->fresh()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();

        $this->putJson('/api/'.$resource.'/'.$target->id, ['nombre' => $other->name])
            ->assertUnprocessable()->assertJsonPath('error.nombre.0', 'The nombre has already been taken.');

        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_log', $auditCount);
    }

    public function test_registration_rejects_name_longer_than_storage_limit(): void
    {
        $this->postJson('/api/register', ['name' => str_repeat('a', 256), 'email' => 'new@example.test', 'password' => 'password123'])
            ->assertUnprocessable()->assertJsonPath('error.name.0', 'The name field must not be greater than 255 characters.');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('activity_log', 0);
    }

    private function authenticateFor(string $permission): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$permission]);
        $this->actingAs($actor, 'api');
    }

    private function confiscationPayload(): array
    {
        return ['fecha' => '2024-02-29', 'observacion' => 'Observation', 'direccion' => 'Address',
            'departamento' => 'Department', 'municipalidad' => 'Municipality', 'latitud' => '12.3', 'longitud' => '98.7'];
    }
}
