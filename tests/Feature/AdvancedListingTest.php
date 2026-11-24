<?php

namespace Tests\Feature;

use App\Models\Activitylog;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdvancedListingTest extends TestCase
{
    /** @return array<string, array{string, class-string, string}> */
    public static function listings(): array
    {
        $cases = [];
        foreach ([
            'user' => User::class, 'role' => Role::class, 'permission' => Permission::class,
            'drug' => Drug::class, 'drugPresentation' => DrugPresentation::class,
            'weapon' => Weapon::class, 'ammunition' => Ammunition::class,
            'confiscation' => Confiscation::class, 'activity' => Activitylog::class,
            'drugConfiscation' => DrugConfiscation::class,
            'weaponConfiscation' => WeaponConfiscation::class,
            'ammunitionConfiscation' => AmmunitionConfiscation::class,
        ] as $resource => $model) {
            $cases[$resource] = [$resource, $model, 'active'];
            if ($resource !== 'activity') {
                $cases[$resource.' deleted'] = [$resource, $model, 'deleted'];
            }
            if (str_ends_with($resource, 'Confiscation')) {
                $cases[$resource.' parent'] = [$resource, $model, 'parent'];
            }
        }

        return $cases;
    }

    #[DataProvider('listings')]
    public function test_every_listing_supports_typed_identity_and_sparse_fields(string $resource, string $model, string $mode): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.'.view']);
        if (str_ends_with($resource, 'Confiscation')) {
            $this->createEvidenceParents();
        }
        $record = $model::factory()->create();
        $path = '/api/'.$resource;
        if ($mode === 'deleted') {
            $record->delete();
            $path .= '/deleted';
        } elseif ($mode === 'parent') {
            $path .= '/'.$record->confiscation_id.'/confiscation';
        }

        $this->actingAs($actor, 'api')->getJson($path.'?'.http_build_query([
            'filter' => ['identificador' => $record->id], 'fields' => ['identificador'], 'include' => '',
        ]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data', [['identificador' => $record->id]]);
    }

    public function test_search_is_grouped_with_legacy_filters_and_combines_text_columns(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['confiscation.view']);
        $first = Confiscation::factory()->create(['observation' => 'NEEDLE', 'direction' => 'Road', 'department' => 'Allowed']);
        $second = Confiscation::factory()->create(['observation' => 'Other', 'direction' => 'Needle road', 'department' => 'Allowed']);
        Confiscation::factory()->create(['observation' => 'Needle', 'direction' => 'Needle road', 'department' => 'Excluded']);

        $response = $this->actingAs($actor, 'api')->getJson('/api/confiscation?search=needle&departamento=Allowed&fields=observacion');

        $response->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data', [
            ['identificador' => $first->id, 'observacion' => 'NEEDLE'],
            ['identificador' => $second->id, 'observacion' => 'Other'],
        ]);
    }

    public function test_search_and_typed_text_filters_treat_wildcards_and_sql_as_literal_values(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        $match = Drug::factory()->create(['description' => 'Dose 50%_!']);
        Drug::factory()->create(['description' => 'Dose 50AA!']);
        $this->actingAs($actor, 'api');

        $this->getJson('/api/drug?'.http_build_query(['search' => '50%_!', 'filter' => ['descripcion' => 'DOSE']]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.identificador', $match->id);
        $this->getJson('/api/drug?'.http_build_query(['search' => "' OR 1=1 --"]))
            ->assertOk()->assertJsonPath('total', 0);
    }

    public function test_typed_numeric_filters_use_equality_while_legacy_filters_remain_partial(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        Drug::factory()->create(['id' => 12]);
        Drug::factory()->create(['id' => 21]);
        $this->actingAs($actor, 'api');

        $this->getJson('/api/drug?identificador=2')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/drug?filter[identificador]=12')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.identificador', 12);
    }

    public function test_fields_and_relations_limit_sql_columns_and_preserve_keys_for_historical_relations(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drugConfiscation.view']);
        $this->createEvidenceParents();
        $drug = Drug::firstOrFail();
        $record = DrugConfiscation::factory()->create(['amount' => 5, 'weight' => 12.5]);
        $drug->delete();
        $attributes = [];
        DrugConfiscation::retrieved(function (DrugConfiscation $item) use (&$attributes): void {
            $attributes[] = array_keys($item->getAttributes());
        });

        $response = $this->actingAs($actor, 'api')->getJson('/api/drugConfiscation?'.http_build_query([
            'fields' => 'cantidad', 'include' => 'droga',
            'filter' => ['droga' => $drug->id, 'cantidad' => 5, 'peso' => 12.5],
        ]));

        $response->assertOk()->assertJsonPath('data', [[
            'identificador' => $record->id, 'cantidad' => 5,
            'droga' => ['identificador' => $drug->id, 'descripcion' => $drug->description],
        ]]);
        $this->assertSame([['id', 'amount', 'drug_id']], $attributes);
    }

    public function test_relation_search_and_date_filters_work_without_loading_omitted_relations(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drugConfiscation.view']);
        $this->createEvidenceParents();
        $drug = Drug::firstOrFail();
        $drug->update(['description' => 'Unique needle']);
        Confiscation::firstOrFail()->update(['date' => '2026-06-15']);
        $record = DrugConfiscation::factory()->create();
        DrugConfiscation::factory()->create(['confiscation_id' => Confiscation::factory()->create(['date' => '2026-07-01'])->id]);
        $hydratedDrugs = 0;
        Drug::retrieved(function () use (&$hydratedDrugs): void {
            $hydratedDrugs++;
        });

        $this->actingAs($actor, 'api')->getJson('/api/drugConfiscation?'.http_build_query([
            'search' => 'needle', 'fields' => 'cantidad', 'include' => '',
            'filter' => ['droga_descripcion' => 'Unique', 'fecha_desde' => '2026-06-01', 'fecha_hasta' => '2026-06-30'],
        ]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data', [['identificador' => $record->id, 'cantidad' => $record->amount]]);

        $this->assertSame(0, $hydratedDrugs);
    }

    public function test_date_range_includes_both_boundaries(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['confiscation.view']);
        $first = Confiscation::factory()->create(['date' => '2026-06-01']);
        $last = Confiscation::factory()->create(['date' => '2026-06-30']);
        Confiscation::factory()->create(['date' => '2026-07-01']);

        $this->actingAs($actor, 'api')->getJson('/api/confiscation?'.http_build_query([
            'filter' => ['fecha_desde' => '2026-06-01', 'fecha_hasta' => '2026-06-30'], 'fields' => 'fecha',
        ]))->assertOk()->assertJsonPath('data', [
            ['identificador' => $first->id, 'fecha' => '2026-06-01'],
            ['identificador' => $last->id, 'fecha' => '2026-06-30'],
        ]);
    }

    public function test_assignment_filters_and_selection_keep_only_direct_permission_ids(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.view', 'role.view']);
        $permission = Permission::findOrCreate('drug.view', 'api');
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);
        $target = User::factory()->create();
        $target->assignRole($role);
        $target->givePermissionTo($permission);
        $this->actingAs($actor, 'api');

        $this->getJson('/api/user?'.http_build_query(['filter' => ['rol' => $role->id, 'permiso' => $permission->id], 'fields' => 'nombre', 'include' => 'permisos']))
            ->assertOk()->assertJsonPath('data', [['identificador' => $target->id, 'nombre' => $target->name, 'permisos' => [$permission->id]]]);
        $this->getJson('/api/role?'.http_build_query(['filter' => ['permiso' => $permission->id], 'fields' => 'nombre', 'include' => '']))
            ->assertOk()->assertJsonPath('data', [['identificador' => $role->id, 'nombre' => $role->name]]);
    }

    public function test_activity_fields_can_select_properties_without_exposing_internal_attributes(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['activity.view']);
        $record = Activitylog::factory()->create(['properties' => ['note' => 'Safe value'], 'created_at' => '2026-06-15 12:30:00']);

        $this->actingAs($actor, 'api')->getJson('/api/activity?'.http_build_query([
            'filter' => ['identificador' => $record->id, 'fecha' => '2026-06-15'], 'fields' => 'cambios',
        ]))->assertOk()->assertJsonPath('data', [['identificador' => $record->id, 'cambios' => ['note' => 'Safe value']]]);
    }

    public function test_next_page_preserves_advanced_filters_and_explicitly_empty_includes(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.view']);
        User::factory()->count(3)->create(['name' => 'Match']);
        $this->actingAs($actor, 'api');
        $response = $this->getJson('/api/user?'.http_build_query([
            'search' => 'Match', 'filter' => ['nombre' => 'Match'], 'fields' => ['nombre'], 'include' => '', 'per_page' => 2,
        ]))->assertOk()->assertJsonPath('total', 3);

        $next = $this->getJson($response->json('next_page_url'))->assertOk()->assertJsonPath('current_page', 2)->assertJsonCount(1, 'data');

        $this->assertSame(['identificador', 'nombre'], array_keys($next->json('data.0')));
    }

    /** @return array<string, array{string, array<string, mixed>, string}> */
    public static function invalidParameters(): array
    {
        return [
            'secret field' => ['drug', ['fields' => 'password'], 'fields.0'],
            'internal column' => ['drug', ['fields' => 'description'], 'fields.0'],
            'empty fields' => ['drug', ['fields' => ''], 'fields'],
            'duplicate fields' => ['drug', ['fields' => ['descripcion', 'descripcion']], 'fields.0'],
            'object fields' => ['drug', ['fields' => ['key' => 'descripcion']], 'fields'],
            'unknown relation' => ['user', ['include' => 'secret'], 'include.0'],
            'nested relation' => ['user', ['include' => 'roles.permissions'], 'include.0'],
            'internal relation' => ['user', ['include' => 'permissions'], 'include.0'],
            'object includes' => ['user', ['include' => ['key' => 'roles']], 'include'],
            'array search' => ['drug', ['search' => ['term']], 'search'],
            'long search' => ['drug', ['search' => str_repeat('x', 256)], 'search'],
            'scalar filter' => ['drug', ['filter' => 'text'], 'filter'],
            'unknown filter' => ['drug', ['filter' => ['description' => 'x']], 'filter'],
            'injected key' => ['drug', ['filter' => ['id OR 1=1' => 'x']], 'filter'],
            'fraction id' => ['drug', ['filter' => ['identificador' => '1.5']], 'filter.identificador'],
            'array id' => ['drug', ['filter' => ['identificador' => [1]]], 'filter.identificador'],
            'negative id' => ['drug', ['filter' => ['identificador' => -1]], 'filter.identificador'],
            'non numeric weight' => ['drugConfiscation', ['filter' => ['peso' => 'not-numeric']], 'filter.peso'],
            'invalid date' => ['confiscation', ['filter' => ['fecha' => '2026-02-30']], 'filter.fecha'],
            'reversed range' => ['confiscation', ['filter' => ['fecha_desde' => '2026-07-01', 'fecha_hasta' => '2026-06-01']], 'filter.fecha_hasta'],
        ];
    }

    #[DataProvider('invalidParameters')]
    public function test_invalid_advanced_parameters_return_422_before_running_a_listing(string $resource, array $parameters, string $error): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.'.view']);

        $this->actingAs($actor, 'api')->getJson('/api/'.$resource.'?'.http_build_query($parameters))
            ->assertUnprocessable()->assertJsonPath('code', 422)->assertJsonValidationErrors($error, 'error');
    }

    public function test_authorization_still_precedes_advanced_validation(): void
    {
        $this->getJson('/api/drug?fields=password')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'api')->getJson('/api/drug?fields=password')->assertForbidden();
    }

    private function createEvidenceParents(): void
    {
        Confiscation::factory()->create();
        Drug::factory()->create();
        DrugPresentation::factory()->create();
        Weapon::factory()->create();
        Ammunition::factory()->create();
    }
}
