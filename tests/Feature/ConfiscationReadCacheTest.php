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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\StatisticsTestCase;

class ConfiscationReadCacheTest extends StatisticsTestCase
{
    /** @var list<string> */
    private array $queries = [];

    /**
     * Real commits are essential here. Each application gets a new SQLite PDO;
     * no shared RefreshDatabase connection or external database is used.
     */
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->beforeRefreshingDatabase();
        config([
            'cache.stores.database.connection' => 'sqlite',
            'cache.stores.database.lock_connection' => 'sqlite',
        ]);
        $this->migrateDatabases();
    }

    public static function catalogTypes(): array
    {
        return [
            'drug' => ['drug', 'drugs', Drug::class, DrugConfiscation::class],
            'weapon' => ['weapon', 'weapons', Weapon::class, WeaponConfiscation::class],
            'ammunition' => ['ammunition', 'ammunitions', Ammunition::class, AmmunitionConfiscation::class],
        ];
    }

    public static function cacheStores(): array
    {
        return ['array' => ['array'], 'database' => ['database']];
    }

    #[DataProvider('catalogTypes')]
    public function test_graph_hits_skip_queries_until_thirty_seconds_have_elapsed(
        string $type, string $field, string $catalogClass, string $detailClass,
    ): void {
        $this->freezeSecond();
        $this->authenticate();
        $catalog = $catalogClass::factory()->create(['description' => 'Selected']);
        $this->evidence($type, $catalog, $detailClass);
        $url = $this->graphUrl($type, $field, [$catalog->id]);

        $fresh = $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3)->json();
        $this->assertSame(2, $this->resultQueryCount());
        $this->travel(29)->seconds();
        $this->read($url)->assertOk()->assertExactJson($fresh);
        $this->assertSame(0, $this->resultQueryCount());
        $this->travel(1)->seconds();
        $this->read($url)->assertOk()->assertExactJson($fresh);
        $this->assertSame(2, $this->resultQueryCount());
    }

    public function test_equivalent_validated_filters_reuse_graph_and_map_results(): void
    {
        $this->authenticate();
        $first = Drug::factory()->create();
        $second = Drug::factory()->create();
        $this->evidence('drug', $first, DrugConfiscation::class);
        $graph = $this->read($this->graphUrl('drug', 'drugs', [$first->id, $second->id]))->assertOk()->json();
        $map = $this->read($this->mapUrl([$first->id, $second->id]))->assertOk()->json();

        $this->read('/api/drugGraphIndex?'.http_build_query([
            'drugs' => json_encode([(string) $second->id, (string) $first->id]),
            'typeGraph' => 'pie', 'end_date' => '2026-12-31', 'start_date' => '2026-01-01',
            'period' => 'month', 'ignored' => 'value',
        ]))->assertOk()->assertExactJson($graph);
        $this->assertSame(0, $this->resultQueryCount());
        $this->read('/api/confiscation/map?'.http_build_query([
            'ammunitions' => '[]', 'drugs' => json_encode([$second->id, $first->id]),
            'end_date' => '2026-12-31', 'start_date' => '2026-01-01',
        ]))->assertOk()->assertExactJson($map);
        $this->assertSame(0, $this->resultQueryCount());
    }

    public function test_changed_period_range_and_selection_have_independent_results(): void
    {
        $this->authenticate();
        $first = Drug::factory()->create(['description' => 'First']);
        $second = Drug::factory()->create(['description' => 'Second']);
        $this->evidence('drug', $first, DrugConfiscation::class);
        $this->evidence('drug', $second, DrugConfiscation::class, 7);
        $this->read($this->graphUrl('drug', 'drugs', [$first->id]))->assertOk()->assertJsonPath('pieData.0.value', 3);

        $this->read($this->graphUrl('drug', 'drugs', [$first->id], ['period' => 'quarter']))
            ->assertOk()->assertJsonPath('lineBarData.0.name', '2026-Q1');
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($this->graphUrl('drug', 'drugs', [$first->id], ['end_date' => '2026-01-01']))
            ->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($this->graphUrl('drug', 'drugs', [$second->id]))
            ->assertOk()->assertJsonPath('pieData', [['name' => 'Second', 'value' => 7]]);
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($this->mapUrl([$first->id]))->assertOk()->assertJsonPath('mapItems.0.drug_confiscations.0.cantidad', 3);
        $this->read($this->mapUrl([$second->id]))->assertOk()->assertJsonPath('mapItems.0.drug_confiscations.0.cantidad', 7);
        $this->assertSame(4, $this->resultQueryCount());
        $this->read($this->mapUrl([$first->id], ['end_date' => '2026-01-01']))
            ->assertOk()->assertExactJson(['mapItems' => []]);
        $this->assertSame(1, $this->resultQueryCount());
    }

    #[DataProvider('cacheStores')]
    public function test_map_hits_skip_queries_and_model_hydration_until_expiry(string $store): void
    {
        config(['cache.default' => $store]);
        $this->freezeSecond();
        $this->authenticate();
        $catalog = Drug::factory()->create();
        $this->evidence('drug', $catalog, DrugConfiscation::class);
        $hydrated = 0;
        foreach ([Confiscation::class, DrugConfiscation::class] as $model) {
            $model::retrieved(function () use (&$hydrated): void {
                $hydrated++;
            });
        }
        $url = $this->mapUrl([$catalog->id]);

        $fresh = $this->read($url)->assertOk()->assertJsonCount(1, 'mapItems')->json();
        $this->assertSame(4, $this->resultQueryCount());
        $this->assertSame(2, $hydrated);
        $this->travel(29)->seconds();
        $this->read($url)->assertOk()->assertExactJson($fresh);
        $this->assertSame(0, $this->resultQueryCount());
        $this->assertSame(2, $hydrated);
        $this->travel(1)->seconds();
        $this->read($url)->assertOk()->assertExactJson($fresh);
        $this->assertSame(4, $this->resultQueryCount());
        $this->assertSame(4, $hydrated);
    }

    #[DataProvider('catalogTypes')]
    public function test_item_create_update_delete_and_restore_invalidate_graph_and_map(
        string $type, string $field, string $catalogClass, string $detailClass,
    ): void {
        $this->authenticate();
        $catalog = $catalogClass::factory()->create();
        $item = $this->evidence($type, $catalog, $detailClass);
        $graph = $this->graphUrl($type, $field, [$catalog->id]);
        $map = $this->mapUrl([], [$field => [$catalog->id]]);
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->read($map)->assertOk()->assertJsonCount(1, 'mapItems');

        $other = $this->evidence($type, $catalog, $detailClass, 7, $item->confiscation);
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.value', 10);
        $this->read($map)->assertOk()->assertJsonCount(2, 'mapItems.0.'.$type.'_confiscations');
        $item->update(['amount' => 5]);
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.value', 12);
        $this->read($map)->assertOk()->assertJsonPath('mapItems.0.'.$type.'_confiscations.0.cantidad', 5);
        $other->delete();
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.value', 5);
        $this->read($map)->assertOk()->assertJsonCount(1, 'mapItems');
        $other->restore();
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.value', 12);
        $this->read($map)->assertOk()->assertJsonCount(2, 'mapItems.0.'.$type.'_confiscations');
    }

    #[DataProvider('catalogTypes')]
    public function test_catalog_rename_delete_and_restore_refresh_historical_labels(
        string $type, string $field, string $catalogClass, string $detailClass,
    ): void {
        $this->authenticate();
        $catalog = $catalogClass::factory()->create(['description' => 'Before']);
        $this->evidence($type, $catalog, $detailClass);
        $graph = $this->graphUrl($type, $field, [$catalog->id]);
        $map = $this->mapUrl([], [$field => [$catalog->id]]);
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.name', 'Before');
        $this->read($map)->assertOk();

        $catalog->update(['description' => 'After']);
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.name', 'After');
        $nameField = ['drug' => 'droga_nombre', 'weapon' => 'arma_nombre', 'ammunition' => 'municion_nombre'][$type];
        $this->read($map)->assertOk()->assertJsonPath('mapItems.0.'.$type.'_confiscations.0.'.$nameField, 'After');
        $catalog->delete();
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.name', 'After');
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($map)->assertOk()->assertJsonCount(1, 'mapItems');
        $this->assertSame(4, $this->resultQueryCount());
        $catalog->restore();
        $this->read($graph)->assertOk()->assertJsonPath('pieData.0.name', 'After');
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($map)->assertOk()->assertJsonCount(1, 'mapItems');
        $this->assertSame(4, $this->resultQueryCount());
    }

    public function test_parent_date_delete_and_restore_invalidate_all_graph_types(): void
    {
        $this->authenticate();
        $parent = Confiscation::factory()->create(['date' => '2026-01-15']);
        $urls = [];
        foreach (self::catalogTypes() as [$type, $field, $catalogClass, $detailClass]) {
            $catalog = $catalogClass::factory()->create();
            $this->evidence($type, $catalog, $detailClass, 3, $parent);
            $urls[] = $this->graphUrl($type, $field, [$catalog->id]);
        }
        foreach ($urls as $url) {
            $this->read($url)->assertOk()->assertJsonPath('lineBarData.0.name', '2026-01');
        }
        $parent->update(['date' => '2026-02-15']);
        foreach ($urls as $url) {
            $this->read($url)->assertOk()->assertJsonPath('lineBarData.0.name', '2026-02');
        }
        $this->travel(61)->seconds();
        $parent->delete();
        foreach ($urls as $url) {
            $this->read($url)->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        }
        $parent->restore();
        foreach ($urls as $url) {
            $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        }
    }

    public function test_unrelated_changes_preserve_other_graph_domains_and_cache_entries(): void
    {
        $this->authenticate();
        $drug = Drug::factory()->create();
        $weapon = Weapon::factory()->create();
        $item = $this->evidence('drug', $drug, DrugConfiscation::class);
        $this->evidence('weapon', $weapon, WeaponConfiscation::class);
        $url = $this->graphUrl('weapon', 'weapons', [$weapon->id]);
        $fresh = $this->read($url)->assertOk()->json();
        Cache::put('unrelated-entry', 'preserved', 120);

        $item->update(['amount' => 9]);
        $this->read($url)->assertOk()->assertExactJson($fresh);
        $this->assertSame(0, $this->resultQueryCount());
        $this->assertSame('preserved', Cache::get('unrelated-entry'));
    }

    public function test_parent_coordinates_observation_delete_and_restore_refresh_map(): void
    {
        $this->authenticate();
        $drug = Drug::factory()->create();
        $item = $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->mapUrl([$drug->id]);
        $this->read($url)->assertOk()->assertJsonCount(1, 'mapItems');

        $item->confiscation->update(['latitude' => 15.25, 'length' => -87.5, 'observation' => 'Updated']);
        $this->read($url)->assertOk()->assertJsonPath('mapItems.0.latitud', 15.25)
            ->assertJsonPath('mapItems.0.longitud', -87.5)->assertJsonPath('mapItems.0.observacion', 'Updated');
        $item->confiscation->delete();
        $this->read($url)->assertOk()->assertExactJson(['mapItems' => []]);
        $item->confiscation->restore();
        $this->read($url)->assertOk()->assertJsonCount(1, 'mapItems');
    }

    public function test_http_item_update_commits_before_invalidating_graph_and_map(): void
    {
        $actor = $this->authenticate();
        $this->grantPermissions($actor, ['drugConfiscation.update']);
        $drug = Drug::factory()->create();
        $item = $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $map = $this->mapUrl([$drug->id]);
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->read($map)->assertOk();

        $this->putJson('/api/drugConfiscation/'.$item->id, [
            'cantidad' => 9, 'peso' => 2.75, 'decomiso' => $item->confiscation_id,
            'droga' => $drug->id, 'presentacion' => $item->drug_presentation_id,
        ])->assertOk()->assertJsonPath('data.cantidad', 9);
        $this->assertDatabaseHas('drug_confiscations', ['id' => $item->id, 'amount' => 9, 'weight' => 2.75]);
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 9);
        $this->read($map)->assertOk()->assertJsonPath('mapItems.0.drug_confiscations.0.cantidad', 9)
            ->assertJsonPath('mapItems.0.drug_confiscations.0.peso', 2.75);
    }

    public function test_empty_graph_and_map_results_are_reused_and_item_creation_invalidates_them(): void
    {
        $this->authenticate();
        $drug = Drug::factory()->create();
        $parent = Confiscation::factory()->create(['date' => '2026-01-15']);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $map = $this->mapUrl([$drug->id]);
        $this->read($url)->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->read($map)->assertOk()->assertExactJson(['mapItems' => []]);

        $this->read($url)->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->assertSame(0, $this->resultQueryCount());
        $this->read($map)->assertOk()->assertExactJson(['mapItems' => []]);
        $this->assertSame(0, $this->resultQueryCount());
        $this->evidence('drug', $drug, DrugConfiscation::class, 3, $parent);
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->read($map)->assertOk()->assertJsonCount(1, 'mapItems');
    }

    #[DataProvider('cacheStores')]
    public function test_empty_database_initializes_cache_and_reuses_empty_results(string $store): void
    {
        config(['cache.default' => $store]);
        $this->authenticate();
        $graph = $this->graphUrl('drug', 'drugs', []);
        $map = $this->mapUrl([]);

        $this->read($graph)->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->read($map)->assertOk()->assertExactJson(['mapItems' => []]);
        $this->read($graph)->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
        $this->assertSame(0, $this->resultQueryCount());
        $this->read($map)->assertOk()->assertExactJson(['mapItems' => []]);
        $this->assertSame(0, $this->resultQueryCount());
    }

    public function test_users_and_endpoints_have_independent_cache_entries(): void
    {
        $first = $this->authenticate();
        $drug = Drug::factory()->create();
        $weapon = Weapon::factory()->create();
        $this->evidence('drug', $drug, DrugConfiscation::class);
        $this->evidence('weapon', $weapon, WeaponConfiscation::class, 7);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $map = $this->mapUrl([$drug->id]);
        $graphResult = $this->read($url)->assertOk()->json();
        $mapResult = $this->read($map)->assertOk()->json();
        $this->read($this->graphUrl('weapon', 'weapons', [$weapon->id]))->assertOk()->assertJsonPath('pieData.0.value', 7);
        $this->assertSame(2, $this->resultQueryCount());

        $this->authenticate();
        $this->read($url)->assertOk()->assertExactJson($graphResult);
        $this->assertSame(2, $this->resultQueryCount());
        $this->read($map)->assertOk()->assertExactJson($mapResult);
        $this->assertSame(4, $this->resultQueryCount());
        $this->actingAs($first, 'api');
        $this->read($url)->assertOk()->assertExactJson($graphResult);
        $this->assertSame(0, $this->resultQueryCount());
    }

    public function test_cached_results_still_require_authentication_permissions_and_valid_filters(): void
    {
        $actor = $this->authenticate();
        $drug = Drug::factory()->create();
        $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $map = $this->mapUrl([$drug->id]);
        $this->read($url)->assertOk();
        $this->read($map)->assertOk();

        $actor->revokePermissionTo(['graph.view', 'map.view']);
        $this->read($url)->assertForbidden()->assertJsonPath('code', 403);
        $this->read($map)->assertForbidden();
        $this->grantPermissions($actor, ['graph.view', 'map.view']);
        $this->read($url.'&period=day')->assertUnprocessable()->assertJsonStructure(['error' => ['period']]);
        $this->read($map.'&drugs=invalid')->assertUnprocessable()->assertJsonStructure(['error' => ['drugs']]);
        $this->resetJwtAuthentication();
        $this->read($url)->assertUnauthorized();
        $this->read($map)->assertUnauthorized();
        $this->actingAs($actor, 'api');
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->assertSame(0, $this->resultQueryCount());
    }

    public function test_role_permission_revocation_denies_a_previously_cached_graph(): void
    {
        $actor = User::factory()->create();
        $permission = Permission::findOrCreate('graph.view', 'api');
        $role = Role::factory()->create(['guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $actor->assignRole($role);
        $this->actingAs($actor, 'api');
        $drug = Drug::factory()->create();
        $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $this->read($url)->assertOk();

        $role->revokePermissionTo($permission);
        $this->actingAs($actor->fresh(), 'api');
        $this->read($url)->assertForbidden();
        $role->givePermissionTo($permission);
        $this->actingAs($actor->fresh(), 'api');
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
    }

    #[DataProvider('cacheStores')]
    public function test_transaction_reads_are_not_cached_and_rollback_preserves_committed_results(string $store): void
    {
        config(['cache.default' => $store]);
        $this->authenticate();
        $drug = Drug::factory()->create();
        $item = $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);

        DB::beginTransaction();
        $item->update(['amount' => 9]);
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 9);
        $this->assertSame(2, $this->resultQueryCount());
        DB::rollBack();
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->assertSame(0, $this->resultQueryCount());
        DB::transaction(function () use ($item): void {
            $item->update(['amount' => 12]);
        });
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 12);
        $this->assertSame(2, $this->resultQueryCount());
    }

    public function test_query_failure_is_not_cached_and_next_request_recovers(): void
    {
        config(['app.debug' => false]);
        $this->authenticate();
        $drug = Drug::factory()->create();
        $this->evidence('drug', $drug, DrugConfiscation::class);
        $url = $this->graphUrl('drug', 'drugs', [$drug->id]);
        $migration = require database_path('migrations/2026_11_06_011904_create_data_graph_drug_confiscation_view.php');
        $migration->down();

        $this->read($url)->assertInternalServerError()->assertJsonPath('code', 500);
        $migration->up();
        $this->read($url)->assertOk()->assertJsonPath('pieData.0.value', 3);
        $this->assertSame(2, $this->resultQueryCount());
    }

    private function authenticate(): User
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, ['graph.view', 'map.view']);
        $this->actingAs($actor, 'api');

        return $actor;
    }

    private function evidence(string $type, Model $catalog, string $detailClass, int $amount = 3, ?Confiscation $parent = null): Model
    {
        $parent ??= Confiscation::factory()->create(['date' => '2026-01-15']);
        $attributes = [$type.'_id' => $catalog->id, 'confiscation_id' => $parent->id, 'amount' => $amount];
        if ($type === 'drug') {
            $attributes += ['drug_presentation_id' => DrugPresentation::factory()->create()->id, 'weight' => 1.25];
        }

        return $detailClass::factory()->create($attributes);
    }

    private function graphUrl(string $type, string $field, array $ids, array $overrides = []): string
    {
        return '/api/'.$type.'GraphIndex?'.http_build_query(array_replace([
            'period' => 'month', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'typeGraph' => 'bar', $field => json_encode($ids),
        ], $overrides));
    }

    private function mapUrl(array $drugs, array $overrides = []): string
    {
        return '/api/confiscation/map?'.http_build_query(array_replace([
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'drugs' => json_encode($drugs),
        ], $overrides));
    }

    private function read(string $url): TestResponse
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->getJson($url);
        $this->queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return $response;
    }

    private function resultQueryCount(): int
    {
        return count(array_filter($this->queries, fn (string $query): bool => ! str_contains($query, 'count(*)')
            && preg_match('/from ["`](data_graph_|confiscations|drug_confiscations|weapon_confiscations|ammunition_confiscations|drugs|weapons|ammunitions)/', $query)));
    }
}
