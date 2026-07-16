<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;

class CrudTestRecordDefinition implements CrudDefinition
{
    public function model(): string
    {
        return CrudTestRecord::class;
    }

    public function title(): string
    {
        return 'Test Records';
    }

    public function description(): ?string
    {
        return null;
    }

    public function emptyLabel(): ?string
    {
        return null;
    }

    public function columns(): array
    {
        return [
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable(),
            CrudColumn::make('email'),
            CrudColumn::make('internal_notes')->hidden(),
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255']),
            CrudField::make('email', ['required', 'email', 'max:255'])->unique(),
        ];
    }
}
