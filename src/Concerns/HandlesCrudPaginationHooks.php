<?php

namespace Modules\Crud\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait HandlesCrudPaginationHooks
{
    /**
     * @param  Builder<Model>  $query
     */
    public function beforePaginate(Builder $query): void {}

    /**
     * @param  LengthAwarePaginator<int, Model>|Paginator<int, Model>  $paginator
     */
    public function afterPaginate(LengthAwarePaginator|Paginator $paginator): void {}
}
