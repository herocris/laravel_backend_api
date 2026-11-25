<?php

namespace App\Support\Query;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Applies the full index pipeline—pagination, sorting, search, filters,
 * field selection, includes, and soft-delete scoping—to an Eloquent query.
 */
class IndexQueryApplier
{
    /** @param array<string, mixed> $options */
    public function __construct(#[Config('index_query')] private readonly array $options) {}

    /**
     * Run the complete index pipeline and return a paginated result.
     *
     * @param  Builder  $query  The base Eloquent query builder.
     * @param  array<string, mixed>  $validated  Validated query-string parameters.
     * @param  array<string, mixed>  $config  Definition array from an `*IndexDefinition::definition()` call.
     */
    public function apply(Builder $query, array $validated, array $config = []): LengthAwarePaginator
    {
        $this->applyOnlyTrashed($query, (bool) ($validated['only_trashed'] ?? false));
        $this->applyIncludes($query, $validated, $config);
        $this->applyFields($query, $validated['fields'] ?? null, $config['selectable'] ?? $config['fields'] ?? []);
        $this->applyTopLevelPartialMatchFilters($query, $validated, $config['fields'] ?? []);
        $this->applySearch($query, (string) ($validated['search'] ?? ''), $config['searchable'] ?? []);
        $this->applyFilters($query, $validated['filter'] ?? [], $config['filters'] ?? []);
        $this->applySort($query, $validated, $config);

        return $this->paginate($query, $validated, $config);
    }

    /**
     * Scope the query to soft-deleted records only when `only_trashed` is requested.
     */
    private function applyOnlyTrashed(Builder $query, bool $onlyTrashed): void
    {
        if ($onlyTrashed && $this->options['allow_only_trashed'] && $this->usesSoftDeletes($query)) {
            $query->onlyTrashed();
        }
    }

    /**
     * @param  array{include?: list<string>}  $validated
     * @param  array{includes?: list<string>|array<string, string>, include_loads?: array<string, list<string>>, with?: list<string>}  $config
     */
    private function applyIncludes(Builder $query, array $validated, array $config): void
    {
        if (! array_key_exists('include', $validated)) {
            $query->with($config['with'] ?? []);

            return;
        }

        $includeMap = $this->nameMap($config['includes'] ?? []);
        $requested = array_values(array_intersect($validated['include'] ?? [], array_keys($includeMap)));
        $loads = array_map(fn (string $name): string => $includeMap[$name], $requested);
        foreach ($requested as $name) {
            $loads = array_merge($loads, $config['include_loads'][$name] ?? []);
        }

        if ($loads !== []) {
            $query->with(array_unique($loads));
        }
    }

    /**
     * Restrict the SELECT clause to whitelisted columns, always keeping the primary key.
     *
     * @param  list<string>|array<string, string>  $selectableFields
     */
    private function applyFields(Builder $query, mixed $rawFields, array $selectableFields): void
    {
        $requestedFields = is_array($rawFields) ? $rawFields : [];

        if (empty($requestedFields) || empty($selectableFields)) {
            return;
        }

        $fieldMap = $this->nameMap($selectableFields);
        $requested = array_values(array_intersect($requestedFields, array_keys($fieldMap)));
        $fields = array_map(fn (string $name): string => $fieldMap[$name], $requested);
        $keyName = $query->getModel()->getKeyName();

        if ($this->options['preserve_computed_fields']) {
            $fields[] = $keyName;
        } else {
            array_unshift($fields, $keyName);
        }

        foreach (array_keys($query->getEagerLoads()) as $include) {
            $relationName = explode('.', $include)[0];
            $relation = $query->getModel()->{$relationName}();

            if ($relation instanceof BelongsTo) {
                $fields[] = $relation->getForeignKeyName();
            }

            if ($relation instanceof MorphTo) {
                $fields[] = $relation->getMorphType();
            }

            if ($relation instanceof HasOneOrMany) {
                $fields[] = $relation->getLocalKeyName();
            }

            if ($relation instanceof BelongsToMany) {
                $fields[] = $relation->getParentKeyName();
            }
        }

        $fields = array_unique($fields);
        if ($this->options['qualify_columns']) {
            $fields = array_map($query->qualifyColumn(...), $fields);
        }

        if ($this->options['preserve_computed_fields']) {
            $query->getQuery()->columns = array_values(array_filter(
                $query->getQuery()->columns ?? [],
                fn (mixed $column): bool => ! is_string($column) || str_contains(strtolower($column), ' as '),
            ));
            $query->addSelect($fields);

            return;
        }

        $query->select($fields);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<string>|array<string, string>  $fields
     */
    private function applyTopLevelPartialMatchFilters(Builder $query, array $validated, array $fields): void
    {
        if (! $this->options['allow_legacy_filters']) {
            return;
        }

        foreach ($this->nameMap($fields) as $field => $column) {
            if (isset($validated[$field])) {
                self::whereContains($query, $column, $validated[$field]);
            }
        }
    }

    /**
     * @param  list<Closure|string>  $searchable
     */
    private function applySearch(Builder $query, string $searchTerm, array $searchable): void
    {
        $term = trim($searchTerm);

        if ($term === '' || empty($searchable)) {
            return;
        }

        $query->where(function (Builder $builder) use ($searchable, $term): void {
            foreach ($searchable as $field) {
                if ($field instanceof Closure) {
                    $builder->orWhere(fn (Builder $part) => $field($part, $term));

                    continue;
                }

                if ($this->options['literal_search']) {
                    $builder->orWhere(fn (Builder $part) => self::whereContains($part, $field, $term));
                } else {
                    $builder->orWhere($field, 'like', '%'.$term.'%');
                }
            }
        });
    }

    /**
     * Apply per-field filters from the request against the definition's filter map.
     *
     * Each filter contains validation rules and either a column name (equality)
     * or a closure that applies a complex query.
     *
     * @param  array<string, array{query: string|Closure, operator?: string}>  $filterMap
     */
    private function applyFilters(Builder $query, mixed $rawFilters, array $filterMap): void
    {
        $filters = is_array($rawFilters) ? $rawFilters : [];

        if (empty($filters) || empty($filterMap)) {
            return;
        }

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || ! array_key_exists($key, $filterMap)) {
                continue;
            }

            $handler = $filterMap[$key]['query'];

            if ($handler instanceof Closure) {
                $handler($query, $value);
            } elseif (($filterMap[$key]['operator'] ?? '=') === 'like') {
                self::whereContains($query, $handler, $value);
            } else {
                $query->where($this->columnForQuery($query, $handler), $value);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $config
     */
    private function applySort(Builder $query, array $validated, array $config): void
    {
        $sort = $this->resolveSort(
            $validated[$this->options['sort_parameter']] ?? null,
            $this->nameMap($config[$this->options['sortable_definition_key']] ?? []),
            $config['default_sort'] ?? $this->options['default_sort'],
        );

        $direction = (string) ($validated[$this->options['direction_parameter']] ?? $config['default_direction'] ?? 'desc');
        $tieBreakDirection = $this->options['tie_break_direction'];

        if ($sort !== null) {
            $query->orderBy($this->columnForQuery($query, $sort), $direction);
        }

        if ($sort !== $query->getModel()->getKeyName() || $tieBreakDirection !== null) {
            $query->orderBy($query->getModel()->getQualifiedKeyName(), $tieBreakDirection ?? $direction);
        }
    }

    /**
     * Resolve the sort column, falling back to the default if the requested
     * column is not in the sortable whitelist.
     *
     * @param  array<string, string>  $sortable
     */
    private function resolveSort(mixed $rawSort, array $sortable, ?string $defaultSort): ?string
    {
        if (! is_string($rawSort)) {
            return $defaultSort;
        }

        return $sortable === [] ? $rawSort : ($sortable[$rawSort] ?? $defaultSort);
    }

    /**
     * @param  array{per_page?: int|string, page?: int|string}  $validated
     * @param  array{default_per_page?: int}  $config
     */
    private function paginate(Builder $query, array $validated, array $config): LengthAwarePaginator
    {
        $perPage = (int) ($validated['per_page'] ?? $config['default_per_page'] ?? $this->options['default_per_page']);
        $page = $this->options['use_validated_page'] ? (int) ($validated['page'] ?? 1) : null;
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->options['append_query_string'] ? $paginator->withQueryString() : $paginator;
    }

    public static function whereContains(Builder $query, string $column, string $term): void
    {
        $qualifiedColumn = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
        $value = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($term));
        $query->whereRaw("LOWER({$qualifiedColumn}) LIKE ? ESCAPE '!'", ['%'.$value.'%']);
    }

    private function columnForQuery(Builder $query, string $column): string
    {
        return $this->options['qualify_columns'] ? $query->qualifyColumn($column) : $column;
    }

    /**
     * @param  list<string>|array<string, string>  $names
     * @return array<string, string>
     */
    private function nameMap(array $names): array
    {
        return array_is_list($names) ? array_combine($names, $names) : $names;
    }

    /**
     * Determine whether the query's model uses the SoftDeletes trait.
     */
    private function usesSoftDeletes(Builder $query): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($query->getModel()), true);
    }
}
