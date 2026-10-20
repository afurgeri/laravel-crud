<?php

namespace Modules\Crud;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;
use Modules\Crud\Contracts\HasCrudPaginationHooks;
use Modules\Crud\Contracts\HasDefaultCrudPageSize;

class CrudIndexManager
{
    public function __construct(private readonly CrudQueryManager $queries) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  Closure(Builder<Model>): mixed|null  $scope
     * @return LengthAwarePaginator<int, Model>|Paginator<int, Model>
     */
    public function paginate(
        CrudDefinition $definition,
        int $page = 1,
        ?int $perPage = null,
        ?string $sort = null,
        string $direction = 'asc',
        ?string $search = null,
        array $filters = [],
        ?Closure $scope = null,
    ): LengthAwarePaginator|Paginator {
        $query = $this->queries->for($definition, $sort, $direction, $search, $filters, $scope);

        $paginator = match (config('crud.pagination.driver', 'length_aware')) {
            'length_aware' => $query->paginate(perPage: $this->resolvePerPage($definition, $perPage), page: $page),
            'simple' => $query->simplePaginate(perPage: $this->resolvePerPage($definition, $perPage), page: $page),
            default => throw new InvalidArgumentException('Unsupported CRUD pagination driver.'),
        };

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
}
