<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Crud\Concerns\HandlesCrudPaginationHooks;
use Modules\Crud\Contracts\HasCrudPaginationHooks;

class CrudTestRecordPaginationDefinition extends CrudTestRecordDefinition implements HasCrudPaginationHooks
{
    use HandlesCrudPaginationHooks;

    /** @var list<string> */
    public static array $events = [];

    /**
     * @param  Builder<Model>  $query
     */
    public function beforePaginate(Builder $query): void
    {
        self::$events[] = 'beforePaginate';
        $query->where('name', 'Ada');
    }

    /**
     * @param  LengthAwarePaginator<int, Model>  $paginator
     */
    public function afterPaginate(LengthAwarePaginator $paginator): void
    {
        self::$events[] = 'afterPaginate';
        $paginator->through(fn (Model $model): array => [
            'label' => $model->name,
        ]);
    }
}
