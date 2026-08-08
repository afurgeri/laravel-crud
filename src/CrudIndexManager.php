<?php

namespace Modules\Crud;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudPaginationHooks;
use Modules\Crud\Contracts\HasDefaultCrudPageSize;
use Modules\Crud\Exceptions\InvalidCrudFilterRange;
use Modules\Crud\Exceptions\InvalidCrudFilterValue;
use Modules\Crud\Exceptions\InvalidCrudSortColumn;
use MongoDB\BSON\ObjectId;

class CrudIndexManager
{
    public function __construct(
        private readonly CrudFilterValues $filterValues,
        private readonly CrudSortValues $sortValues,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(
        CrudDefinition $definition,
        int $page = 1,
        ?int $perPage = null,
        ?string $sort = null,
        string $direction = 'asc',
        ?string $search = null,
        array $filters = [],
    ): LengthAwarePaginator {
        if ($definition instanceof AuthorizesCrudIndex) {
            $definition->authorizeViewAny();
        }

        ['column' => $sort, 'direction' => $direction] = $this->sortValues->for($definition, $sort, $direction);

        $model = $definition->model();
        /** @var Model $instance */
        $instance = new $model;

        $query = $instance->newQuery();

        if ($definition instanceof EagerLoadsCrudRelations) {
            $query->with($definition->eagerLoads());
        }

        $this->applySearch($query, $definition, $search, $instance);

        if ($definition instanceof HasCrudFilters) {
            $this->applyFilters($query, $definition, $filters);
        }

        if ($sort !== null) {
            if (! in_array($sort, $this->sortableColumnNames($definition), true)) {
                throw InvalidCrudSortColumn::forColumn($sort);
            }

            $query->orderBy($sort, $direction);
        }

        if ($definition instanceof HasCrudPaginationHooks) {
            $definition->beforePaginate($query);
        }

        $paginator = $query->paginate(perPage: $this->resolvePerPage($definition, $perPage), page: $page);

        if ($definition instanceof HasCrudPaginationHooks) {
            $definition->afterPaginate($paginator);
        }

        return $paginator;
    }

    private function resolvePerPage(CrudDefinition $definition, ?int $perPage): int
    {
        $resolved = $perPage;

        if ($resolved === null && $definition instanceof HasDefaultCrudPageSize) {
            $resolved = $definition->defaultPageSize();
        }

        return min(max($resolved ?? 10, 1), 100);
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applySearch(Builder $query, CrudDefinition $definition, ?string $search, Model $model): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $columns = $this->searchableColumnNames($definition);
        $terms = $this->searchTerms($search);

        if ($columns === [] || $terms === []) {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $terms, $model): void {
            foreach ($terms as $term) {
                $query->where(function (Builder $termQuery) use ($columns, $term, $model): void {
                    foreach ($columns as $column) {
                        if ($column === $model->getKeyName()) {
                            $this->applyKeySearch($termQuery, $model, $term);

                            continue;
                        }

                        $termQuery->orWhere($column, 'like', '%'.$this->escapeLikeTerm($term).'%');
                    }
                });
            }
        });
    }

    /**
     * @return list<string>
     */
    private function searchTerms(string $search): array
    {
        return preg_split('/[\s-]+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function escapeLikeTerm(string $term): string
    {
        return addcslashes($term, '%_\\');
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyKeySearch(Builder $query, Model $model, string $search): void
    {
        if ($model->getConnection()->getDriverName() !== 'mongodb') {
            $query->orWhere($model->getKeyName(), '=', $search);

            return;
        }

        if (! class_exists(ObjectId::class)) {
            $query->orWhere($model->getKeyName(), '=', $search);

            return;
        }

        try {
            $key = new ObjectId($search);
        } catch (\InvalidArgumentException) {
            $query->orWhereRaw(['_id' => ['$in' => []]]);

            return;
        }

        $query->orWhere($model->getKeyName(), '=', $key);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, HasCrudFilters $definition, array $filters): void
    {
        $definitionFilters = $definition->filters();
        $effectiveFilters = $this->filterValues->for($definitionFilters, $filters);

        foreach ($definitionFilters as $filter) {
            if ($filter->type() === 'datetime' && array_key_exists($filter->name(), $effectiveFilters)) {
                $value = $effectiveFilters[$filter->name()];

                if (is_string($value) && $value !== '') {
                    $effectiveFilters[$filter->name()] = CrudTemporal::utcDateTime($value);
                }
            }
        }

        $this->validateRanges($definitionFilters, $effectiveFilters);
        $this->validateMaxDates($definitionFilters, $effectiveFilters);

        foreach ($definitionFilters as $filter) {
            $value = $effectiveFilters[$filter->name()] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $this->applyFilter($query, $filter, $value);
        }
    }

    /**
     * Ensures that for every declared range group (filters sharing a `range()` tag), the
     * submitted lower bound isn't after the submitted upper bound.
     *
     * @param  list<CrudFilter>  $definitionFilters
     * @param  array<string, mixed>  $filters
     */
    private function validateRanges(array $definitionFilters, array $filters): void
    {
        $groups = collect($definitionFilters)
            ->filter(fn (CrudFilter $filter): bool => $filter->rangeGroup() !== null)
            ->groupBy(fn (CrudFilter $filter): string => $filter->rangeGroup());

        /** @var string $group */
        foreach ($groups as $group => $groupFilters) {
            $lower = $groupFilters->first(fn (CrudFilter $filter): bool => in_array($filter->comparisonOperator(), ['>', '>='], true));
            $upper = $groupFilters->first(fn (CrudFilter $filter): bool => in_array($filter->comparisonOperator(), ['<', '<='], true));

            if (! $lower instanceof CrudFilter || ! $upper instanceof CrudFilter) {
                continue;
            }

            $lowerValue = $filters[$lower->name()] ?? null;
            $upperValue = $filters[$upper->name()] ?? null;

            if (! $lowerValue || ! $upperValue) {
                continue;
            }

            if ($lowerValue > $upperValue) {
                throw InvalidCrudFilterRange::forGroup($group);
            }
        }
    }

    /**
     * Rejects a submitted date filter value that falls after the filter's configured
     * maximum date. This is a security boundary, not just UX polish — the frontend
     * datepicker already prevents picking such a date, but a crafted request could
     * still submit one directly, so it must also be rejected here.
     *
     * @param  list<CrudFilter>  $definitionFilters
     * @param  array<string, mixed>  $filters
     */
    private function validateMaxDates(array $definitionFilters, array $filters): void
    {
        foreach ($definitionFilters as $filter) {
            if ($filter->type() !== 'date') {
                continue;
            }

            $max = $filter->resolvedMaxDate();

            if ($max === null) {
                continue;
            }

            $value = $filters[$filter->name()] ?? null;

            if (! $value) {
                continue;
            }

            if ($value > $max) {
                throw InvalidCrudFilterValue::exceedsMaximumDate($filter->name(), $max);
            }
        }
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyFilter(Builder $query, CrudFilter $filter, mixed $value): void
    {
        if ($filter->isMultiple()) {
            if ($filter->isRelation()) {
                $query->whereHas(
                    $filter->relationName(),
                    fn (Builder $query): Builder => $query->whereIn($filter->relationColumn(), $value),
                );

                return;
            }

            $query->whereIn($filter->column(), $value);

            return;
        }

        if ($filter->isRelation()) {
            $query->whereHas(
                $filter->relationName(),
                fn (Builder $query): Builder => $query->where($filter->relationColumn(), $value),
            );

            return;
        }

        match ($filter->type()) {
            'text' => $query->where($filter->column(), 'like', '%'.(string) $value.'%'),
            'date' => $query->whereDate($filter->column(), $filter->comparisonOperator(), $value),
            'time' => $query->whereTime($filter->column(), $filter->comparisonOperator(), $value),
            default => $query->where($filter->column(), $filter->comparisonOperator(), $value),
        };
    }

    /**
     * @return list<string>
     */
    private function visibleColumnNames(CrudDefinition $definition): array
    {
        return $this->columnNamesWhere(
            $definition,
            fn (CrudColumn $column): bool => $column->isVisible() && ! $column->isComputed(),
        );
    }

    /**
     * @return list<string>
     */
    private function sortableColumnNames(CrudDefinition $definition): array
    {
        return $this->columnNamesWhere(
            $definition,
            fn (CrudColumn $column): bool => $column->isSortable() && ! $column->isComputed(),
        );
    }

    /**
     * @return list<string>
     */
    private function searchableColumnNames(CrudDefinition $definition): array
    {
        return $this->columnNamesWhere(
            $definition,
            fn (CrudColumn $column): bool => $column->isSearchable() && ! $column->isComputed(),
        );
    }

    /**
     * @return list<string>
     */
    private function columnNamesWhere(CrudDefinition $definition, \Closure $predicate): array
    {
        return array_values(collect($definition->columns())
            ->filter($predicate)
            ->map(fn (CrudColumn $column): string => $column->name())
            ->all());
    }
}
