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

        $this->applyIncludes($query, $parameters, $definition);
        $this->applyFields($query, $parameters['fields'] ?? null, $definition['selectable'] ?? $fields);
        $this->applyLegacyFilters($query, $parameters, $fields);
        $this->applySearch($query, $parameters['search'] ?? '', $definition['searchable'] ?? []);
        $this->applyTypedFilters($query, $parameters['filter'] ?? [], $definition['filters'] ?? []);
        $this->applySort($query, $parameters, $fields);

        return $this->paginate($query, $parameters);
    }

    public static function whereContains(Builder $query, string $column, string $term): void
    {
        $qualifiedColumn = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
        $value = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($term));
        $query->whereRaw("LOWER({$qualifiedColumn}) LIKE ? ESCAPE '!'", ['%'.$value.'%']);
    }

    /**
     * @param  array{include?: list<string>}  $parameters
     * @param  array{includes?: array<string, string>, with?: list<string>}  $definition
     */
    private function applyIncludes(Builder $query, array $parameters, array $definition): void
    {
        $includes = $definition['includes'] ?? [];
        $loads = array_key_exists('include', $parameters)
            ? array_map(fn (string $name): string => $includes[$name], $parameters['include'])
            : ($definition['with'] ?? []);
        $query->with($loads);
    }

    /**
     * @param  list<string>|null  $requested
     * @param  array<string, string>  $allowed
     */
    private function applyFields(Builder $query, ?array $requested, array $allowed): void
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

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string>  $fields
     */
    private function applyLegacyFilters(Builder $query, array $parameters, array $fields): void
    {
        foreach ($fields as $field => $column) {
            if (isset($parameters[$field])) {
                self::whereContains($query, $column, $parameters[$field]);
            }
        }
    }

    /** @param list<Closure|string> $searchable */
    private function applySearch(Builder $query, string $searchTerm, array $searchable): void
    {
        $term = trim($searchTerm);
        if ($term === '' || $searchable === []) {
            return;
        }

        $query->where(function (Builder $search) use ($searchable, $term): void {
            foreach ($searchable as $column) {
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

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, array{query: Closure|string, operator?: string}>  $filterMap
     */
    private function applyTypedFilters(Builder $query, array $filters, array $filterMap): void
    {
        foreach ($filters as $field => $value) {
            $filter = $filterMap[$field];
            if ($filter['query'] instanceof Closure) {
                $filter['query']($query, $value);
            } elseif (($filter['operator'] ?? '=') === 'like') {
                self::whereContains($query, $filter['query'], $value);
            } else {
                $query->where($query->qualifyColumn($filter['query']), $value);
            }
        }
    }

    /**
     * @param  array{sort_by?: string, type?: string}  $parameters
     * @param  array<string, string>  $fields
     */
    private function applySort(Builder $query, array $parameters, array $fields): void
    {
        if (isset($parameters['sort_by'])) {
            $query->orderBy($query->qualifyColumn($fields[$parameters['sort_by']]), $parameters['type'] ?? 'desc');
        }

        $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /** @param array{per_page?: int|string, page?: int|string} $parameters */
    private function paginate(Builder $query, array $parameters): LengthAwarePaginator
    {
        return $query->paginate((int) ($parameters['per_page'] ?? 10), ['*'], 'page', (int) ($parameters['page'] ?? 1));
    }
}
