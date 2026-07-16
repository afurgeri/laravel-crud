<?php

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudSortValues;

test('for returns the definition default when no sort is requested', function () {
    $definition = new class implements CrudDefinition, HasDefaultCrudSort
    {
        public function model(): string
        {
            return Model::class;
        }

        public function title(): string
        {
            return 'Users';
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
            return [];
        }

        public function fields(): array
        {
            return [];
        }

        public function defaultSortColumn(): string
        {
            return 'name';
        }

        public function defaultSortDirection(): string
        {
            return 'desc';
        }
    };

    $sort = app(CrudSortValues::class)->for($definition, null, 'asc');

    expect($sort)->toBe(['column' => 'name', 'direction' => 'desc']);
});

test('for keeps the explicit sort when one is requested', function () {
    $definition = new class implements CrudDefinition, HasDefaultCrudSort
    {
        public function model(): string
        {
            return Model::class;
        }

        public function title(): string
        {
            return 'Users';
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
            return [];
        }

        public function fields(): array
        {
            return [];
        }

        public function defaultSortColumn(): string
        {
            return 'name';
        }

        public function defaultSortDirection(): string
        {
            return 'desc';
        }
    };

    $sort = app(CrudSortValues::class)->for($definition, 'id', 'asc');

    expect($sort)->toBe(['column' => 'id', 'direction' => 'asc']);
});
