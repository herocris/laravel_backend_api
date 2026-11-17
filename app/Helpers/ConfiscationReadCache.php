<?php

namespace App\Helpers;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConfiscationReadCache
{
    private const TTL_SECONDS = 30;

    /**
     * Call after HTTP authorization and validation. D12 defines no record-level
     * restrictions; each api user still has an independent cache namespace.
     *
     * @param  array<string, mixed>  $filters
     * @param  Closure(): array<string, mixed>  $resolve
     * @return array<string, mixed>
     */
    public function remember(string $scope, int|string $userId, array $filters, Closure $resolve): array
    {
        $connection = DB::connection();
        if ($connection->transactionLevel() > 0) {
            return $resolve();
        }

        unset($filters['typeGraph']);
        foreach (['drugs', 'weapons', 'ammunitions'] as $field) {
            $filters[$field] = array_map(intval(...), $filters[$field] ?? []);
            sort($filters[$field], SORT_NUMERIC);
        }
        ksort($filters);

        $versionKey = $this->versionKey($scope);
        $version = Cache::get($versionKey);
        if ($version === null) {
            Cache::add($versionKey, (string) Str::uuid(), 86400);
            $version = Cache::get($versionKey);
        }
        $key = 'confiscation-read:v1:'.hash('sha256', json_encode([
            $scope, $version, $connection->getName(), $connection->getDatabaseName(),
            'api', (string) $userId, $filters,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($key, self::TTL_SECONDS, $resolve);
    }

    /**
     * Rotate affected domains after commit. Old payloads expire naturally;
     * computations already in flight can only populate an obsolete version.
     */
    public function invalidate(Model $model): void
    {
        $scopes = match ($model::class) {
            Confiscation::class => ['graph:drug', 'graph:weapon', 'graph:ammunition', 'map'],
            Drug::class, DrugConfiscation::class => ['graph:drug', 'map'],
            Weapon::class, WeaponConfiscation::class => ['graph:weapon', 'map'],
            Ammunition::class, AmmunitionConfiscation::class => ['graph:ammunition', 'map'],
            default => [],
        };

        $invalidate = function () use ($scopes): void {
            foreach ($scopes as $scope) {
                Cache::forever($this->versionKey($scope), (string) Str::uuid());
            }
        };

        if ($model->getConnection()->transactionLevel() > 0) {
            $model->getConnection()->afterCommit($invalidate);
        } else {
            $invalidate();
        }
    }

    private function versionKey(string $scope): string
    {
        return 'confiscation-read:v1:version:'.$scope;
    }
}
