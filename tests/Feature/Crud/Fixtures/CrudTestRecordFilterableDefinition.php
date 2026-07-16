<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudFilter;

class CrudTestRecordFilterableDefinition extends CrudTestRecordDefinition implements HasCrudFilters
{
    public function filters(): array
    {
        return [
            CrudFilter::make('name')->text(),
            CrudFilter::make('created_from', 'created_at')->date()->operator('>=')->range('created_at'),
            CrudFilter::make('created_to', 'created_at')->date()->operator('<=')->range('created_at'),
            CrudFilter::make('note')->select(fn (): array => [])->relation('notes', 'id'),
            CrudFilter::make('min_id', 'id')->number()->operator('>='),
            CrudFilter::make('joined_before', 'created_at')->date()->operator('<=')->maxDate(fn (): string => now()->toDateString()),
        ];
    }
}
