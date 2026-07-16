<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\CrudColumn;

class CrudTestRecordSearchableDefinition extends CrudTestRecordDefinition
{
    public function columns(): array
    {
        return [
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('email')->searchable(),
            CrudColumn::make('internal_notes')->hidden(),
        ];
    }
}
