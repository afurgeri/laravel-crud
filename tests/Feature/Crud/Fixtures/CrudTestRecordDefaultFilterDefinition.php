<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudFilter;

class CrudTestRecordDefaultFilterDefinition extends CrudTestRecordDefinition implements HasCrudFilters
{
    public function filters(): array
    {
        return [
            CrudFilter::make('name')->text()->default(fn (): string => 'Ada'),
        ];
    }
}
