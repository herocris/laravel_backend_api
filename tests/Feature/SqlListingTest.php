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
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SqlListingTest extends TestCase
{
    public static function listings(): array
    {
        $cases = [];

        foreach ([
            'user' => [User::class, 'nombre', 'name', 'Listado 11'],
            'role' => [Role::class, 'nombre', 'name', 'Listado 11'],
            'permission' => [Permission::class, 'nombre', 'name', 'Listado 11'],
            'drug' => [Drug::class, 'descripcion', 'description', 'Listado 11'],
            'drugPresentation' => [DrugPresentation::class, 'descripcion', 'description', 'Listado 11'],
            'weapon' => [Weapon::class, 'descripcion', 'description', 'Listado 11'],
            'ammunition' => [Ammunition::class, 'descripcion', 'description', 'Listado 11'],
            'confiscation' => [Confiscation::class, 'observacion', 'observation', 'Listado 11'],
            'drugConfiscation' => [DrugConfiscation::class, 'cantidad', 'amount', 77],
            'weaponConfiscation' => [WeaponConfiscation::class, 'cantidad', 'amount', 77],
            'ammunitionConfiscation' => [AmmunitionConfiscation::class, 'cantidad', 'amount', 77],
            'activity' => [Activitylog::class, 'descripcion', 'description', 'Listado 11'],
        ] as $resource => [$model, $field, $column, $value]) {
            $cases[$resource.' active'] = [$resource, $model, $field, $column, $value, 'active'];

            if ($resource !== 'activity') {
                $cases[$resource.' deleted'] = [$resource, $model, $field, $column, $value, 'deleted'];
            }

            if (str_ends_with($resource, 'Confiscation')) {
                $cases[$resource.' parent'] = [$resource, $model, $field, $column, $value, 'parent'];
            }
        }

        return $cases;
    }

    #[DataProvider('listings')]
    public function test_each_listing_filters_and_pages_in_sql_with_stable_ties_and_loaded_relations(
        string $resource, string $model, string $field, string $column, string|int $value, string $mode,
    ): void {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$resource.'.view']);
        $attributes = [$column => $value];
        if (str_ends_with($resource, 'Confiscation')) {
            $attributes += $this->evidenceParents($model);
        }
        $records = $model::factory()->count(5)->sequence(fn (Sequence $sequence): array => array_replace($attributes, [
            $column => in_array($resource, ['role', 'permission'], true) ? $value.' '.$sequence->index : $value,
        ]))->create();
        $outsider = $model::factory()->create(array_replace($attributes, [
            $column => in_array($resource, ['role', 'permission'], true) ? $value.' outsider' : $value,
        ]));
        $path = '/api/'.$resource;
        if ($mode === 'deleted') {
            $records->each->delete();
            $path .= '/deleted';
        } elseif ($mode === 'parent') {
            $outsider->update(['confiscation_id' => Confiscation::factory()->create()->id]);
            $path .= '/'.$attributes['confiscation_id'].'/confiscation';
        } elseif ($resource === 'activity') {
            $outsider->update(['description' => 'Other event']);
        } else {
            $outsider->delete();
        }
        $query = [$field => (string) $value, 'per_page' => 2, 'page' => 2];
        $this->actingAs($actor, 'api');
        $this->assertTrue($actor->can($resource.'.view'));
        $hydrated = 0;
        $model::retrieved(function () use (&$hydrated): void {
            $hydrated++;
        });
        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($actor, 'api')->getJson($path.'?'.http_build_query($query));
        } finally {
            Model::preventLazyLoading(false);
        }

        $response->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.from', 3)->assertJsonPath('meta.to', 4)->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('data.0.identificador', $records[2]->id)
            ->assertJsonPath('data.1.identificador', $records[3]->id);
        $this->assertSame(2, $hydrated);
        parse_str(parse_url($response->json('links.next'), PHP_URL_QUERY), $nextQuery);
        $this->assertSame('3', $nextQuery['page']);
        $this->assertSame((string) $value, $nextQuery[$field]);
    }

    /** @return array<string, int> */
    private function evidenceParents(string $model): array
    {
        $attributes = ['confiscation_id' => Confiscation::factory()->create()->id];

        return $attributes + match ($model) {
            DrugConfiscation::class => [
                'drug_id' => Drug::factory()->create()->id,
                'drug_presentation_id' => DrugPresentation::factory()->create()->id,
            ],
            WeaponConfiscation::class => ['weapon_id' => Weapon::factory()->create()->id],
            AmmunitionConfiscation::class => ['ammunition_id' => Ammunition::factory()->create()->id],
        };
    }

    public function test_scalar_filters_are_combined_case_insensitive_and_literal(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        $match = Drug::factory()->create(['description' => 'Drug 50%_! dosage', 'logo' => 'needle']);
        Drug::factory()->create(['description' => 'Drug 50AA! dosage', 'logo' => 'needle']);
        Drug::factory()->create(['description' => 'Drug 50%_! dosage', 'logo' => 'other']);

        $this->actingAs($actor, 'api')->getJson('/api/drug?'.http_build_query([
            'descripcion' => 'DRUG 50%_!', 'logo' => 'NEEDLE', 'description' => 'ignored', 'fake' => 'ignored',
        ]))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.identificador', $match->id);
    }

    public static function invalidParameters(): array
    {
        return [
            'below minimum' => ['per_page', '1'],
            'above maximum' => ['per_page', '101'],
            'fraction' => ['per_page', '2.5'],
            'empty limit' => ['per_page', ''],
            'array limit' => ['per_page', ['2']],
            'zero page' => ['page', '0'],
            'negative page' => ['page', '-1'],
            'fraction page' => ['page', '1.5'],
            'array page' => ['page', ['1']],
            'internal order' => ['sort_by', 'description'],
            'unknown order' => ['sort_by', 'missing'],
            'injected order' => ['sort_by', 'id desc; drop table drugs'],
            'nested order' => ['sort_by', 'decomiso.identificador'],
            'array order' => ['sort_by', ['descripcion']],
            'invalid direction' => ['type', 'sideways'],
            'injected direction' => ['type', 'desc; drop table drugs'],
            'array filter' => ['descripcion', ['text']],
        ];
    }

    #[DataProvider('invalidParameters')]
    public function test_invalid_parameters_return_422_even_when_table_is_empty(string $field, mixed $value): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);

        $this->actingAs($actor, 'api')->getJson('/api/drug?'.http_build_query([$field => $value]))
            ->assertUnprocessable()->assertJsonPath('code', 422)->assertJsonStructure(['error' => [$field]]);

        $this->assertDatabaseCount('drugs', 0);
    }

    public static function emptyPages(): array
    {
        return ['empty table' => [0, 1, null], 'no matches' => [3, 1, 'missing'], 'beyond last page' => [3, 8, null]];
    }

    #[DataProvider('emptyPages')]
    public function test_empty_pages_include_consistent_metadata(int $rows, int $page, ?string $filter): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        Drug::factory()->count($rows)->create(['description' => 'present']);

        $this->actingAs($actor, 'api')->getJson('/api/drug?'.http_build_query([
            'per_page' => 2, 'page' => $page, 'descripcion' => $filter,
        ]))->assertOk()->assertJsonPath('data', [])->assertJsonPath('meta.current_page', $page)
            ->assertJsonPath('meta.total', $filter === null ? $rows : 0)->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.from', null)->assertJsonPath('meta.to', null)
            ->assertJsonStructure([
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'last_page'],
            ]);
    }

    public static function directions(): array
    {
        return ['ascending' => ['asc', [0, 1, 2]], 'descending' => ['desc', [2, 0, 1]], 'default descending' => [null, [2, 0, 1]]];
    }

    #[DataProvider('directions')]
    public function test_explicit_sort_uses_id_to_break_ties_across_pages(?string $direction, array $positions): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        $records = Drug::factory()->count(3)->sequence(['description' => 'A'], ['description' => 'A'], ['description' => 'Z'])->create();
        $query = ['sort_by' => 'descripcion', 'type' => $direction, 'per_page' => 2];
        $this->actingAs($actor, 'api');

        $first = $this->getJson('/api/drug?'.http_build_query($query))->assertOk();
        $second = $this->getJson('/api/drug?'.http_build_query($query + ['page' => 2]))->assertOk();

        $this->assertSame(
            array_map(fn (int $position): int => $records[$position]->id, $positions),
            array_merge(array_column($first->json('data'), 'identificador'), array_column($second->json('data'), 'identificador')),
        );
    }

    public function test_user_and_role_lists_preserve_explicit_assignment_ids(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['user.view', 'role.view']);
        $direct = Permission::findOrCreate('drug.view', 'api');
        $inherited = Permission::findOrCreate('weapon.view', 'api');
        $role = Role::factory()->create(['name' => 'Assigned role']);
        $role->givePermissionTo($inherited);
        $target = User::factory()->create(['name' => 'Assigned user']);
        $target->assignRole($role);
        $target->givePermissionTo($direct);
        $this->actingAs($actor, 'api');

        $this->getJson('/api/user?nombre=Assigned')->assertOk()
            ->assertJsonPath('data.0.roles', [$role->id])->assertJsonPath('data.0.permisos', [$direct->id]);
        $this->getJson('/api/role?nombre=Assigned')->assertOk()->assertJsonPath('data.0.permisos', [$inherited->id]);
    }

    public function test_historical_evidence_loads_deleted_parent_and_catalogs(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drugConfiscation.view']);
        $attributes = $this->evidenceParents(DrugConfiscation::class);
        DrugConfiscation::factory()->count(3)->create($attributes);
        Confiscation::findOrFail($attributes['confiscation_id'])->delete();
        Drug::findOrFail($attributes['drug_id'])->delete();
        DrugPresentation::findOrFail($attributes['drug_presentation_id'])->delete();

        $this->actingAs($actor, 'api')->getJson('/api/drugConfiscation?per_page=2')->assertOk()
            ->assertJsonPath('meta.total', 3)->assertJsonPath('data.0.decomiso.identificador', $attributes['confiscation_id'])
            ->assertJsonPath('data.0.droga.identificador', $attributes['drug_id'])
            ->assertJsonPath('data.0.presentacion.identificador', $attributes['drug_presentation_id']);
    }

    public function test_numeric_filters_remain_partial_and_sql_values_cannot_change_query_structure(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        Drug::factory()->create(['id' => 12]);
        Drug::factory()->create(['id' => 21]);
        Drug::factory()->create(['id' => 33]);
        $this->actingAs($actor, 'api');

        $this->getJson('/api/drug?identificador=2')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/drug?'.http_build_query(['descripcion' => "' OR 1=1 --"]))
            ->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_maximum_page_size_bounds_hydration(): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['drug.view']);
        Drug::factory()->count(101)->create();
        $hydrated = 0;
        Drug::retrieved(function () use (&$hydrated): void {
            $hydrated++;
        });

        $this->actingAs($actor, 'api')->getJson('/api/drug?per_page=100')
            ->assertOk()->assertJsonCount(100, 'data')->assertJsonPath('meta.total', 101)->assertJsonPath('meta.per_page', 100);

        $this->assertSame(100, $hydrated);
    }

    public function test_listing_authorization_precedes_query_validation(): void
    {
        $this->getJson('/api/drug?sort_by=invalid')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'api')->getJson('/api/drug?sort_by=invalid')->assertForbidden();
    }
}
