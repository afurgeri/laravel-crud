<?php

namespace Tests\MongoDB\Fixtures;

use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;

class MongoCrudTestRecordDefinition implements CrudDefinition, EagerLoadsCrudRelations, HasCrudFilters
{
    public function model(): string
    {
        return MongoCrudTestRecord::class;
    }

    public function title(): string
    {
        return 'MongoDB Test Records';
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
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('email')->searchable(),
            CrudColumn::make('created_at'),
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

    public function eagerLoads(): array
    {
        return ['notes'];
    }

    public function filters(): array
    {
        return [
            CrudFilter::make('name'),
            CrudFilter::make('created_from', 'created_at')->date()->operator('>='),
            CrudFilter::make('created_to', 'created_at')->date()->operator('<='),
            CrudFilter::make('note')->select([])->relation('notes', 'body'),
        ];
    }
}
