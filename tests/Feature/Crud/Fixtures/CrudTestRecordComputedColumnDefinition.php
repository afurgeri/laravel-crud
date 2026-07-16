<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\CrudColumn;

class CrudTestRecordComputedColumnDefinition extends CrudTestRecordDefinition
{
    public function columns(): array
    {
        return [
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable(),
            CrudColumn::make('tag_count')->computed(),
        ];
    }
}
