<?php

namespace Tests\MongoDB\Fixtures;

use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;

class MongoCityCrudDefinition implements CrudDefinition, EagerLoadsCrudRelations, HasCrudFilters
{
    public function model(): string
    {
        return MongoCity::class;
    }

    public function title(): string
    {
        return 'Cities';
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
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255']),
        ];
    }

    public function eagerLoads(): array
    {
        return ['citizens'];
    }

    public function filters(): array
    {
        return [
            CrudFilter::make('citizen')->select([])->relation('citizens', 'name'),
        ];
    }
}
