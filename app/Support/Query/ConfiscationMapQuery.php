<?php

namespace App\Support\Query;

use App\Models\Ammunition;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\Weapon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfiscationMapQuery
{
    /**
     * Empty or omitted lists select no items of that type; types combine with OR.
     *
     * @param  list<int|string>  $drugs
     * @param  list<int|string>  $weapons
     * @param  list<int|string>  $ammunitions
     * @return Collection<int, Confiscation>
     */
    public function get(string $startDate, string $endDate, array $drugs, array $weapons, array $ammunitions): Collection
    {
        return Confiscation::select('id', 'date as fecha', 'latitude as latitud', 'observation as observacion', 'length as longitud')
            ->with([
                'drugConfiscations' => function (HasMany $query) use ($drugs): void {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'weight as peso', 'drug_id', 'drug_id as droga')
                        ->addSelect([
                            'droga_nombre' => Drug::withTrashed()->select('description')
                                ->whereColumn('drugs.id', 'drug_confiscations.drug_id')
                                ->limit(1),
                        ])
                        ->whereIn('drug_id', $drugs)->orderBy('id');
                },
                'weaponConfiscations' => function (HasMany $query) use ($weapons): void {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'weapon_id', 'weapon_id as arma')
                        ->addSelect([
                            'arma_nombre' => Weapon::withTrashed()->select('description')
                                ->whereColumn('weapons.id', 'weapon_confiscations.weapon_id')
                                ->limit(1),
                        ])
                        ->whereIn('weapon_id', $weapons)->orderBy('id');
                },
                'ammunitionConfiscations' => function (HasMany $query) use ($ammunitions): void {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'ammunition_id', 'ammunition_id as municion')
                        ->addSelect([
                            'municion_nombre' => Ammunition::withTrashed()->select('description')
                                ->whereColumn('ammunitions.id', 'ammunition_confiscations.ammunition_id')
                                ->limit(1),
                        ])
                        ->whereIn('ammunition_id', $ammunitions)->orderBy('id');
                },
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function (Builder $query) use ($drugs, $weapons, $ammunitions): void {
                $query->whereHas('drugConfiscations', fn (Builder $items): Builder => $items->whereIn('drug_id', $drugs))
                    ->orWhereHas('weaponConfiscations', fn (Builder $items): Builder => $items->whereIn('weapon_id', $weapons))
                    ->orWhereHas('ammunitionConfiscations', fn (Builder $items): Builder => $items->whereIn('ammunition_id', $ammunitions));
            })
            ->orderBy('date')->orderBy('id')
            ->get();

    }
}
