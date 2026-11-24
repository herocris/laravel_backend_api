<?php

namespace App\Support\Query;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ConfiscationStatisticsQuery
{
    /**
     * Quantities are counts; drug weights remain in grams in the underlying view.
     *
     * @param  list<int|string>  $catalogIds
     * @return array{lineBarData: list<array<string, int|string>>, pieData: list<array{name: string, value: int}>}
     */
    public function get(string $type, string $period, string $startDate, string $endDate, array $catalogIds): array
    {
        $catalogTable = match ($type) {
            'drug' => 'drugs',
            'weapon' => 'weapons',
            'ammunition' => 'ammunitions',
            default => throw new InvalidArgumentException('Unsupported confiscation type.'),
        };

        $labels = $this->labels($catalogTable, $catalogIds);
        $rows = DB::table('data_graph_'.$type.'_confiscation')
            ->whereIn($type.'_id', $catalogIds)
            ->whereBetween('full_date', [$startDate, $endDate])
            ->select($type.'_id as item_id')
            ->selectRaw($this->periodExpression($period).' as period')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('period', $type.'_id')
            ->orderBy('period')->orderBy($type.'_id')
            ->get();

        $lineBarData = [];
        $totals = [];
        foreach ($rows as $row) {
            $periodLabel = (string) $row->period;
            $label = $labels[$row->item_id];
            $lineBarData[$periodLabel] ??= ['name' => $periodLabel];
            $lineBarData[$periodLabel][$label] = (int) $row->total_amount;
            $totals[$row->item_id] = ($totals[$row->item_id] ?? 0) + (int) $row->total_amount;
        }

        $pieData = [];
        foreach ($labels as $id => $label) {
            if (isset($totals[$id])) {
                $pieData[] = ['name' => $label, 'value' => $totals[$id]];
            }
        }

        return ['lineBarData' => array_values($lineBarData), 'pieData' => $pieData];
    }

    /**
     * Labels depend on the selected identities, including historical catalogs.
     * Reserve original descriptions so ID suffixes cannot hide another item.
     *
     * @param  list<int|string>  $catalogIds
     * @return array<int, string>
     */
    private function labels(string $catalogTable, array $catalogIds): array
    {
        $items = DB::table($catalogTable)->whereIn('id', $catalogIds)->orderBy('description')->orderBy('id')->get(['id', 'description']);
        $counts = $items->countBy('description');
        $reserved = $items->pluck('description')->all();
        $labels = [];
        foreach ($items as $item) {
            $label = $item->description;
            if ($label === 'name' || $counts[$label] > 1) {
                do {
                    $label .= ' (#'.$item->id.')';
                } while (in_array($label, $reserved, true) || in_array($label, $labels, true));
            }
            $labels[(int) $item->id] = $label;
        }

        return $labels;
    }

    private function periodExpression(string $period): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql' => match ($period) {
                'month' => "DATE_FORMAT(full_date, '%Y-%m')",
                'quarter' => "CONCAT(YEAR(full_date), '-Q', QUARTER(full_date))",
                'semester' => "CONCAT(YEAR(full_date), '-S', IF(MONTH(full_date) <= 6, 1, 2))",
                'year' => 'YEAR(full_date)',
                default => throw new InvalidArgumentException('Unsupported statistics period.'),
            },
            'sqlite' => match ($period) {
                'month' => "strftime('%Y-%m', full_date)",
                'quarter' => "printf('%s-Q%d', strftime('%Y', full_date), (CAST(strftime('%m', full_date) AS INTEGER) + 2) / 3)",
                'semester' => "strftime('%Y', full_date) || '-S' || CASE WHEN CAST(strftime('%m', full_date) AS INTEGER) <= 6 THEN 1 ELSE 2 END",
                'year' => "strftime('%Y', full_date)",
                default => throw new InvalidArgumentException('Unsupported statistics period.'),
            },
            default => throw new RuntimeException('Statistics support MySQL and SQLite only.'),
        };
    }
}
