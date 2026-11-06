<?php

namespace Tests\Feature;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\DrugPresentation;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfiscationGraphViewsTest extends TestCase
{
    /**
     * @return array<string, array{string, class-string, class-string}>
     */
    public static function confiscationTypes(): array
    {
        return [
            'drugs' => ['drug', Drug::class, DrugConfiscation::class],
            'ammunitions' => ['ammunition', Ammunition::class, AmmunitionConfiscation::class],
            'weapons' => ['weapon', Weapon::class, WeaponConfiscation::class],
        ];
    }

    #[DataProvider('confiscationTypes')]
    public function test_groups_totals_by_date_and_item_and_excludes_deleted_records(
        string $type,
        string $catalogClass,
        string $detailClass,
    ): void {
        $this->actingAs($this->superAdminLogin());

        $confiscation = Confiscation::factory()->create(['date' => '2026-01-15']);
        $otherConfiscation = Confiscation::factory()->create(['date' => '2026-01-16']);
        $deletedConfiscation = Confiscation::factory()->create([
            'date' => '2026-01-15',
            'deleted_at' => '2026-01-17 00:00:00',
        ]);
        $item = $catalogClass::factory()->create(['description' => 'First item']);
        $otherItem = $catalogClass::factory()->create(['description' => 'Second item']);
        $attributes = [$type.'_id' => $item->id, 'confiscation_id' => $confiscation->id];
        if ($type === 'drug') {
            $attributes['drug_presentation_id'] = DrugPresentation::factory()->create()->id;
            $attributes['weight'] = 1.5;
        }
        $detailClass::factory()->create(array_merge($attributes, ['amount' => 2]));
        $detailClass::factory()->create(array_merge($attributes, ['amount' => 3]));
        $detailClass::factory()->create(array_merge($attributes, [
            'amount' => 100,
            'deleted_at' => '2026-01-17 00:00:00',
        ]));
        $detailClass::factory()->create(array_merge($attributes, [
            'amount' => 100,
            'confiscation_id' => $deletedConfiscation->id,
        ]));
        $detailClass::factory()->create(array_merge($attributes, [
            'amount' => 7,
            'confiscation_id' => $otherConfiscation->id,
        ]));
        $detailClass::factory()->create(array_merge($attributes, [
            'amount' => 11,
            $type.'_id' => $otherItem->id,
        ]));
        $item->delete();

        $view = 'data_graph_'.$type.'_confiscation';

        $this->assertDatabaseCount($view, 3);
        $this->assertDatabaseHas($view, [
            'full_date' => '2026-01-15',
            $type.'_id' => $item->id,
            $type.'_description' => 'First item',
            'total_amount' => 5,
        ]);
        $this->assertDatabaseHas($view, [
            'full_date' => '2026-01-16',
            $type.'_id' => $item->id,
            'total_amount' => 7,
        ]);
        $this->assertDatabaseHas($view, [
            'full_date' => '2026-01-15',
            $type.'_id' => $otherItem->id,
            'total_amount' => 11,
        ]);
        if ($type === 'drug') {
            $this->assertDatabaseHas($view, [
                'full_date' => '2026-01-15',
                'drug_id' => $item->id,
                'total_weight' => 3,
            ]);
        }
    }

    public function test_view_migrations_can_be_reversed_and_reapplied(): void
    {
        foreach (glob(database_path('migrations/*_create_data_graph_*_view.php')) as $path) {
            $migration = require $path;
            $migration->down();
            $migration->up();
        }

        $this->assertDatabaseCount('data_graph_drug_confiscation', 0);
        $this->assertDatabaseCount('data_graph_ammunition_confiscation', 0);
        $this->assertDatabaseCount('data_graph_weapon_confiscation', 0);
    }
}
