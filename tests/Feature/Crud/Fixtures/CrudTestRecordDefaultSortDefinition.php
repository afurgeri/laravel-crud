<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\HasDefaultCrudSort;

class CrudTestRecordDefaultSortDefinition extends CrudTestRecordDefinition implements HasDefaultCrudSort
{
    public function defaultSortColumn(): string
    {
        return 'name';
    }

    public function defaultSortDirection(): string
    {
        return 'desc';
    }
}
