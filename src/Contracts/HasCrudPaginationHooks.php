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
     */
    public function beforePaginate(Builder $query): void;

    public function afterPaginate(LengthAwarePaginator|Paginator $paginator): void;
}
