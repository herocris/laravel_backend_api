<?php

namespace Tests\Feature;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\DrugPresentation;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\StatisticsTestCase;

class StatisticsEndpointsTest extends StatisticsTestCase
{
    public static function catalogTypes(): array
    {
        return [
            'drug' => ['drug', 'drugs', Drug::class, DrugConfiscation::class],
            'weapon' => ['weapon', 'weapons', Weapon::class, WeaponConfiscation::class],
            'ammunition' => ['ammunition', 'ammunitions', Ammunition::class, AmmunitionConfiscation::class],
        ];
    }

    public static function calendarPeriods(): array
    {
        $periods = [
            'month' => ['2025-12' => 1, '2026-01' => 2, '2026-03' => 3, '2026-04' => 5, '2026-06' => 7,
                '2026-07' => 11, '2026-09' => 13, '2026-10' => 17, '2026-12' => 19, '2027-01' => 23],
            'quarter' => ['2025-Q4' => 1, '2026-Q1' => 5, '2026-Q2' => 12, '2026-Q3' => 24, '2026-Q4' => 36, '2027-Q1' => 23],
            'semester' => ['2025-S2' => 1, '2026-S1' => 17, '2026-S2' => 60, '2027-S1' => 23],
            'year' => ['2025' => 1, '2026' => 77, '2027' => 23],
        ];
        $cases = [];
        foreach (self::catalogTypes() as $type => $catalog) {
            foreach ($periods as $period => $totals) {
                $cases[$type.' '.$period] = [...$catalog, $period, $totals];
            }
        }

        return $cases;
    }

    #[DataProvider('calendarPeriods')]
    public function test_calendar_boundaries_return_known_integer_totals(
        string $type, string $field, string $catalogClass, string $detailClass, string $period, array $totals,
    ): void {
        $this->authenticate('graph.view');
        $catalog = $catalogClass::factory()->create(['description' => 'Selected']);
        foreach ([
            '2025-12-31' => 1, '2026-01-01' => 2, '2026-03-31' => 3, '2026-04-01' => 5, '2026-06-30' => 7,
            '2026-07-01' => 11, '2026-09-30' => 13, '2026-10-01' => 17, '2026-12-31' => 19, '2027-01-01' => 23,
        ] as $date => $amount) {
            $this->evidence($type, $detailClass, $catalog, $date, $amount);
        }
        $this->evidence($type, $detailClass, $catalog, '2025-12-30', 1000);
        $this->evidence($type, $detailClass, $catalog, '2027-01-02', 1000);
        $expected = [];
        foreach ($totals as $label => $amount) {
            $expected[] = ['name' => (string) $label, 'Selected' => $amount];
        }

        $this->getJson('/api/'.$type.'GraphIndex?'.http_build_query([
            'period' => $period, 'start_date' => '2025-12-31', 'end_date' => '2027-01-01',
            'typeGraph' => 'line', $field => json_encode([$catalog->id]),
        ]))->assertOk()->assertExactJson([
            'lineBarData' => $expected, 'pieData' => [['name' => 'Selected', 'value' => 101]],
        ]);
    }

    #[DataProvider('catalogTypes')]
    public function test_graph_preserves_distinct_identities_history_and_partial_date_range(
        string $type, string $field, string $catalogClass, string $detailClass,
    ): void {
        $this->authenticate('graph.view');
        $first = $catalogClass::factory()->create(['description' => 'Repeated']);
        $second = $catalogClass::factory()->create(['description' => 'Repeated']);
        $other = $catalogClass::factory()->create(['description' => 'Unselected']);
        $this->evidence($type, $detailClass, $first, '2026-04-15', 2);
        $this->evidence($type, $detailClass, $first, '2026-04-15', 3);
        $this->evidence($type, $detailClass, $second, '2026-04-15', 7);
        $this->evidence($type, $detailClass, $other, '2026-04-15', 100);
        $this->evidence($type, $detailClass, $first, '2026-04-14', 100);
        $this->evidence($type, $detailClass, $first, '2026-04-16', 100);
        $deletedItem = $this->evidence($type, $detailClass, $first, '2026-04-15', 100);
        $deletedItem->delete();
        $deletedParent = $this->evidence($type, $detailClass, $first, '2026-04-15', 100);
        $deletedParent->confiscation->delete();
        $first->delete();
        $firstLabel = 'Repeated (#'.$first->id.')';
        $secondLabel = 'Repeated (#'.$second->id.')';

        $this->getJson('/api/'.$type.'GraphIndex?'.http_build_query([
            'period' => 'quarter', 'start_date' => '2026-04-15', 'end_date' => '2026-04-15',
            'typeGraph' => 'pie', $field => [$second->id, $first->id],
        ]))->assertOk()->assertExactJson([
            'lineBarData' => [['name' => '2026-Q2', $firstLabel => 5, $secondLabel => 7]],
            'pieData' => [['name' => $firstLabel, 'value' => 5], ['name' => $secondLabel, 'value' => 7]],
        ]);
    }

    public function test_reserved_and_suffix_like_descriptions_do_not_overwrite_graph_keys(): void
    {
        $this->authenticate('graph.view');
        $first = Weapon::factory()->create(['description' => 'Same']);
        $second = Weapon::factory()->create(['description' => 'Same']);
        $suffix = Weapon::factory()->create(['description' => 'Same (#'.$first->id.')']);
        $reserved = Weapon::factory()->create(['description' => 'name']);
        foreach ([$first, $second, $suffix, $reserved] as $index => $catalog) {
            $this->evidence('weapon', WeaponConfiscation::class, $catalog, '2026-01-01', $index + 1);
        }
        $firstLabel = 'Same (#'.$first->id.') (#'.$first->id.')';

        $this->getJson('/api/weaponGraphIndex?'.http_build_query([
            'period' => 'year', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'typeGraph' => 'bar',
            'weapons' => [$first->id, $second->id, $suffix->id, $reserved->id],
        ]))->assertOk()->assertJsonPath('lineBarData', [[
            'name' => '2026', $firstLabel => 1, 'Same (#'.$second->id.')' => 2,
            $suffix->description => 3, 'name (#'.$reserved->id.')' => 4,
        ]])->assertJsonCount(4, 'pieData');
    }

    #[DataProvider('catalogTypes')]
    public function test_graph_totals_can_exceed_the_limit_of_an_individual_amount(
        string $type, string $field, string $catalogClass, string $detailClass,
    ): void {
        $this->authenticate('graph.view');
        $catalog = $catalogClass::factory()->create(['description' => 'Large total']);
        $this->evidence($type, $detailClass, $catalog, '2026-01-01', 2147483647);
        $this->evidence($type, $detailClass, $catalog, '2026-01-01', 2147483647);

        $this->getJson('/api/'.$type.'GraphIndex?'.http_build_query([
            'period' => 'year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'typeGraph' => 'bar', $field => [$catalog->id],
        ]))->assertOk()->assertExactJson([
            'lineBarData' => [['name' => '2026', 'Large total' => 4294967294]],
            'pieData' => [['name' => 'Large total', 'value' => 4294967294]],
        ]);
    }

    public static function emptySelections(): array
    {
        $cases = [];
        foreach (self::catalogTypes() as $type => $catalog) {
            $cases[$type.' empty selection'] = [...$catalog, true];
            $cases[$type.' no matches'] = [...$catalog, false];
        }

        return $cases;
    }

    #[DataProvider('emptySelections')]
    public function test_graph_returns_empty_arrays_without_matches(
        string $type, string $field, string $catalogClass, string $detailClass, bool $emptySelection,
    ): void {
        $this->authenticate('graph.view');
        $catalog = $catalogClass::factory()->create();
        $this->evidence($type, $detailClass, $catalog, '2025-12-31', 3);

        $this->getJson('/api/'.$type.'GraphIndex?'.http_build_query([
            'period' => 'month', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'typeGraph' => 'bar',
            $field => json_encode($emptySelection ? [] : [$catalog->id]),
        ]))->assertOk()->assertExactJson(['lineBarData' => [], 'pieData' => []]);
    }

    public static function invalidStatisticsFilters(): array
    {
        $cases = [];
        foreach (['drug' => 'drugs', 'weapon' => 'weapons', 'ammunition' => 'ammunitions'] as $type => $field) {
            foreach (['day', 'total', "month') OR 1=1 --", ['quarter']] as $index => $period) {
                $cases[$type.' invalid period '.$index] = [$type.'GraphIndex', 'period', $period, 'period'];
            }
            foreach ([$type.'GraphIndex', 'confiscation/map'] as $endpoint) {
                $cases[$endpoint.' '.$field.' malformed'] = [$endpoint, $field, '[1,', $field];
                $cases[$endpoint.' '.$field.' nonexistent'] = [$endpoint, $field, '[999999]', $field.'.0'];
                $cases[$endpoint.' '.$field.' object'] = [$endpoint, $field, ['key' => 1], $field];
            }
            $cases[$type.' reversed range'] = [$type.'GraphIndex', 'end_date', '2025-12-31', 'end_date'];
            $cases[$type.' invalid date'] = [$type.'GraphIndex', 'start_date', '2026-02-30', 'start_date'];
        }
        $cases['map reversed range'] = ['confiscation/map', 'end_date', '2025-12-31', 'end_date'];
        $cases['map invalid date'] = ['confiscation/map', 'start_date', '2026-02-30', 'start_date'];

        return $cases;
    }

    #[DataProvider('invalidStatisticsFilters')]
    public function test_invalid_statistics_filters_return_422_before_querying_results(
        string $endpoint, string $field, mixed $value, string $errorField,
    ): void {
        $this->authenticate($endpoint === 'confiscation/map' ? 'map.view' : 'graph.view');
        $payload = array_replace([
            'period' => 'month', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'typeGraph' => 'bar', 'drugs' => '[]', 'weapons' => '[]', 'ammunitions' => '[]',
        ], [$field => $value]);

        $this->getJson('/api/'.$endpoint.'?'.http_build_query($payload))
            ->assertUnprocessable()->assertJsonPath('code', 422)->assertJsonStructure(['error' => [$errorField]]);
    }

    public function test_map_combines_selected_types_with_or_and_preserves_history_weights_and_order(): void
    {
        $this->authenticate('map.view');
        $drug = Drug::factory()->create(['description' => 'Historical drug']);
        $weapon = Weapon::factory()->create(['description' => 'Historical weapon']);
        $ammunition = Ammunition::factory()->create(['description' => 'Historical ammunition']);
        $later = $this->evidence('drug', DrugConfiscation::class, $drug, '2026-07-02', 2);
        $later->update(['weight' => 2.75]);
        $early = $this->evidence('weapon', WeaponConfiscation::class, $weapon, '2026-07-01', 3);
        $last = $this->evidence('ammunition', AmmunitionConfiscation::class, $ammunition, '2026-07-03', 5);
        $this->evidence('drug', DrugConfiscation::class, $drug, '2026-06-30', 100);
        $this->evidence('drug', DrugConfiscation::class, $drug, '2026-07-04', 100);
        $deletedItem = $this->evidence('weapon', WeaponConfiscation::class, $weapon, '2026-07-02', 100);
        $deletedItem->delete();
        $deletedParent = $this->evidence('ammunition', AmmunitionConfiscation::class, $ammunition, '2026-07-02', 100);
        $deletedParent->confiscation->delete();
        $unselected = Drug::factory()->create();
        $this->evidence('drug', DrugConfiscation::class, $unselected, '2026-07-02', 100);
        $drug->delete();
        $weapon->delete();
        $ammunition->delete();

        $response = $this->getJson('/api/confiscation/map?'.http_build_query([
            'start_date' => '2026-07-01', 'end_date' => '2026-07-03',
            'drugs' => json_encode([$drug->id]), 'weapons' => [$weapon->id], 'ammunitions' => [$ammunition->id],
        ]))->assertOk()->assertJsonCount(3, 'mapItems')
            ->assertJsonPath('mapItems.0.id', $early->confiscation_id)
            ->assertJsonPath('mapItems.1.id', $later->confiscation_id)
            ->assertJsonPath('mapItems.2.id', $last->confiscation_id)
            ->assertJsonPath('mapItems.0.weapon_confiscations.0.arma_nombre', 'Historical weapon')
            ->assertJsonPath('mapItems.1.drug_confiscations.0.droga_nombre', 'Historical drug')
            ->assertJsonPath('mapItems.1.drug_confiscations.0.cantidad', 2)
            ->assertJsonPath('mapItems.1.drug_confiscations.0.peso', 2.75)
            ->assertJsonPath('mapItems.2.ammunition_confiscations.0.municion_nombre', 'Historical ammunition');
        $this->assertSame([], $response->json('mapItems.0.drug_confiscations'));
        $this->assertSame([], $response->json('mapItems.1.weapon_confiscations'));
    }

    public function test_map_empty_or_omitted_selections_return_no_items(): void
    {
        $this->authenticate('map.view');
        $drug = Drug::factory()->create();
        $this->evidence('drug', DrugConfiscation::class, $drug, '2026-01-01', 1);
        $range = ['start_date' => '2026-01-01', 'end_date' => '2026-01-01'];

        $this->getJson('/api/confiscation/map?'.http_build_query($range))
            ->assertOk()->assertExactJson(['mapItems' => []]);
        $this->getJson('/api/confiscation/map?'.http_build_query($range + ['drugs' => '[]', 'weapons' => '[]', 'ammunitions' => '[]']))
            ->assertOk()->assertExactJson(['mapItems' => []]);
    }

    private function authenticate(string $permission): void
    {
        $actor = User::factory()->create();
        $this->grantPermissions($actor, [$permission]);
        $this->actingAs($actor, 'api');
    }

    private function evidence(string $type, string $detailClass, Model $catalog, string $date, int $amount): Model
    {
        $parent = Confiscation::factory()->create(['date' => $date]);
        $attributes = [$type.'_id' => $catalog->id, 'confiscation_id' => $parent->id, 'amount' => $amount];
        if ($type === 'drug') {
            $attributes += ['drug_presentation_id' => DrugPresentation::factory()->create()->id, 'weight' => 1.25];
        }

        return $detailClass::factory()->create($attributes);
    }
}
