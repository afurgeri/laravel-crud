<?php

namespace Modules\Crud\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

interface HasCrudPaginationHooks
{
    /**
     * @param  Builder<Model>  $query
     *
     * Applied to both the index and export query so query restrictions remain consistent.
     */
    public function beforePaginate(Builder $query): void;

    /**
     * @param  LengthAwarePaginator<int, Model>|Paginator<int, Model>  $paginator
     */
    public function afterPaginate(LengthAwarePaginator|Paginator $paginator): void;
}
