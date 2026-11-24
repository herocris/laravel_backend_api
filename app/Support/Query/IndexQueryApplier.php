<?php

namespace App\Support\Query;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IndexQueryApplier
{
    /**
     * @param  array<string, mixed>  $parameters  Validated list parameters.
     * @param  array<string, mixed>  $definition
     */
    public function apply(Builder $query, array $parameters, array $definition): LengthAwarePaginator
    {
        $fields = $definition['fields'];
        $includes = $definition['includes'] ?? [];
        $loads = array_key_exists('include', $parameters)
            ? array_map(fn (string $name): string => $includes[$name], $parameters['include'])
            : ($definition['with'] ?? []);
        $query->with($loads);
        $this->selectFields($query, $parameters['fields'] ?? null, $definition['selectable'] ?? $fields);

        foreach ($fields as $field => $column) {
            if (isset($parameters[$field])) {
                self::whereContains($query, $column, $parameters[$field]);
            }
        }

        $term = trim($parameters['search'] ?? '');
        if ($term !== '' && ! empty($definition['searchable'])) {
            $query->where(function (Builder $search) use ($definition, $term): void {
                foreach ($definition['searchable'] as $column) {
                    $search->orWhere(function (Builder $part) use ($column, $term): void {
                        if ($column instanceof Closure) {
                            $column($part, $term);
                        } else {
                            self::whereContains($part, $column, $term);
                        }
                    });
                }
            });
        }

        foreach ($parameters['filter'] ?? [] as $field => $value) {
            $filter = $definition['filters'][$field];
            if ($filter['query'] instanceof Closure) {
                $filter['query']($query, $value);
            } elseif (($filter['operator'] ?? '=') === 'like') {
                self::whereContains($query, $filter['query'], $value);
            } else {
                $query->where($query->qualifyColumn($filter['query']), $value);
            }
        }

        if (isset($parameters['sort_by'])) {
            $query->orderBy($query->qualifyColumn($fields[$parameters['sort_by']]), $parameters['type'] ?? 'desc');
        }

        $query->orderBy($query->getModel()->getQualifiedKeyName());

        return $query->paginate((int) ($parameters['per_page'] ?? 10), ['*'], 'page', (int) ($parameters['page'] ?? 1));
    }

    public static function whereContains(Builder $query, string $column, string $term): void
    {
        $qualifiedColumn = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
        $value = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($term));
        $query->whereRaw("LOWER({$qualifiedColumn}) LIKE ? ESCAPE '!'", ['%'.$value.'%']);
    }

    /**
     * @param  list<string>|null  $requested
     * @param  array<string, string>  $allowed
     */
    private function selectFields(Builder $query, ?array $requested, array $allowed): void
    {
        if ($requested === null) {
            return;
        }

        $columns = [$query->getModel()->getKeyName()];
        foreach ($requested as $field) {
            $columns[] = $allowed[$field];
        }

        foreach (array_keys($query->getEagerLoads()) as $load) {
            $relation = $query->getModel()->{explode('.', $load)[0]}();
            if ($relation instanceof BelongsTo) {
                $columns[] = $relation->getForeignKeyName();
            }
            if ($relation instanceof MorphTo) {
                $columns[] = $relation->getMorphType();
            }
            if ($relation instanceof BelongsToMany) {
                $columns[] = $relation->getParentKeyName();
            }
            if ($relation instanceof HasOneOrMany) {
                $columns[] = $relation->getLocalKeyName();
            }
        }

        $query->select(array_map($query->qualifyColumn(...), array_unique($columns)));
    }
}
