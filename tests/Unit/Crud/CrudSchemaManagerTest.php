<?php

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;
use Modules\Crud\CrudSchemaManager;

test('it builds frontend schema from crud definitions', function () {
    $definition = new class implements CrudDefinition
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
            return [
                CrudColumn::make('id')->sortable(),
                CrudColumn::make('email_address')->hidden(),
            ];
        }

        public function fields(): array
        {
            return [
                CrudField::make('name', ['required', 'string', 'max:255']),
                CrudField::make('email', ['required', 'email'])->email(),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema)->toMatchArray([
        'resource' => 'users',
        'title' => 'Users',
        'description' => null,
        'empty_label' => null,
        'columns' => [
            [
                'name' => 'id',
                'label' => 'Id',
                'sortable' => true,
            ],
        ],
        'fields' => [
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'string', 'max:255'],
                'visible_on_update' => true,
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'email'],
                'visible_on_update' => true,
            ],
        ],
    ]);

    expect(array_column($schema['columns'], 'name'))->toBe(['id']);
});

test('it marks password fields and create only visibility', function () {
    $definition = new class implements CrudDefinition
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
            return [
                CrudField::make('password', ['required', 'string', 'min:8'])->createOnly()->password(),
            ];
        }
    };

    expect(app(CrudSchemaManager::class)->for($definition, 'users'))
        ->toMatchArray([
            'fields' => [
                [
                    'name' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'confirmed' => false,
                    'required' => true,
                    'rules' => ['required', 'string', 'min:8'],
                    'visible_on_update' => false,
                ],
            ],
        ]);
});

test('it marks fields that require confirmation', function () {
    $definition = new class implements CrudDefinition
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
            return [
                CrudField::make('password', ['required', 'string', 'min:8'])->password()->confirmed(),
            ];
        }
    };

    expect(app(CrudSchemaManager::class)->for($definition, 'users'))
        ->toMatchArray([
            'fields' => [
                [
                    'name' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'confirmed' => true,
                    'required' => true,
                    'rules' => ['required', 'string', 'min:8', 'confirmed'],
                    'visible_on_update' => true,
                ],
            ],
        ]);
});

test('it exposes search metadata when a definition has searchable columns', function () {
    $definition = new class implements CrudDefinition
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
            return [
                CrudColumn::make('id'),
                CrudColumn::make('name')->searchable(),
            ];
        }

        public function fields(): array
        {
            return [];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users', search: 'ada');

    expect($schema['search'])->toBe(['enabled' => true, 'value' => 'ada']);
});

test('search is disabled when a definition has no searchable columns', function () {
    $definition = new class implements CrudDefinition
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
            return [CrudColumn::make('id')];
        }

        public function fields(): array
        {
            return [];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['search'])->toBe(['enabled' => false, 'value' => null]);
});

test('it resolves the definition default sort when no sort is requested', function () {
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

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['sort'])->toBe(['column' => 'name', 'direction' => 'desc']);
});

test('it keeps the explicit sort when one is requested', function () {
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

    $schema = app(CrudSchemaManager::class)->for($definition, 'users', sort: 'id', direction: 'asc');

    expect($schema['sort'])->toBe(['column' => 'id', 'direction' => 'asc']);
});

test('it exposes declared filters with their resolved values and options', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('name')->text(),
                CrudFilter::make('role')->select([1 => 'Admin'])->relation('roles'),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users', filterValues: ['name' => 'ada']);

    expect($schema['filters'])->toBe([
        [
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
            'operator' => '=',
            'relation' => false,
            'clearable' => false,
            'range' => null,
            'value' => 'ada',
        ],
        [
            'name' => 'role',
            'label' => 'Role',
            'type' => 'select',
            'operator' => '=',
            'relation' => true,
            'clearable' => false,
            'range' => null,
            'value' => null,
            'options' => [
                ['value' => '1', 'label' => 'Admin'],
            ],
        ],
    ]);
});

test('it passes all filter values to select option closures for cascading filters', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('role')->select([1 => 'Admin', 2 => 'Editor']),
                CrudFilter::make('member')->select(fn (array $filterValues): array => ($filterValues['role'] ?? null) === '1'
                    ? [10 => 'Ada']
                    : [10 => 'Ada', 20 => 'Grace']),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users', filterValues: ['role' => '1']);

    expect($schema['filters'][1]['options'])->toBe([
        ['value' => '10', 'label' => 'Ada'],
    ]);
});

test('it passes default filter values to select option closures for cascading filters', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('role')->select([1 => 'Admin', 2 => 'Editor'])->default('1'),
                CrudFilter::make('member')->select(fn (array $filterValues): array => ($filterValues['role'] ?? null) === '1'
                    ? [10 => 'Ada']
                    : [20 => 'Grace'])->default('20'),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['filters'][0]['value'])->toBe('1')
        ->and($schema['filters'][1]['options'])->toBe([
            ['value' => '10', 'label' => 'Ada'],
        ]);
});

test('it clears a selected option that is no longer available for cascading filters', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('role')->select([1 => 'Admin', 2 => 'Editor']),
                CrudFilter::make('member')->select(fn (array $filterValues): array => ($filterValues['role'] ?? null) === '1'
                    ? [10 => 'Ada']
                    : [20 => 'Grace'])->default('20'),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users', filterValues: [
        'role' => '1',
        'member' => '20',
    ]);

    expect($schema['filters'][1]['value'])->toBeNull()
        ->and($schema['filters'][1]['options'])->toBe([
            ['value' => '10', 'label' => 'Ada'],
        ]);
});

test('it exposes the range group shared by paired filters', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('created_from', 'created_at')->date()->operator('>=')->range('created_at'),
                CrudFilter::make('created_to', 'created_at')->date()->operator('<=')->range('created_at'),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['filters'][0]['range'])->toBe('created_at')
        ->and($schema['filters'][1]['range'])->toBe('created_at');
});

test('it exposes the resolved maximum date for a date filter', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('created_at')->date()->maxDate('2026-12-31'),
                CrudFilter::make('joined_at')->date(),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['filters'][0]['max_date'])->toBe('2026-12-31')
        ->and($schema['filters'][1]['max_date'])->toBeNull();
});

test('it exposes a filter\'s default value when no value was submitted', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('name')->text()->default('Ada'),
            ];
        }
    };

    $withoutValue = app(CrudSchemaManager::class)->for($definition, 'users');
    $withValue = app(CrudSchemaManager::class)->for($definition, 'users', filterValues: ['name' => 'Grace']);

    expect($withoutValue['filters'][0]['value'])->toBe('Ada')
        ->and($withValue['filters'][0]['value'])->toBe('Grace');
});

test('it exposes whether a filter is clearable', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
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

        public function filters(): array
        {
            return [
                CrudFilter::make('created_at')->date(),
                CrudFilter::make('role')->select(['1' => 'Admin'])->clearable(),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['filters'][0]['clearable'])->toBeFalse()
        ->and($schema['filters'][1]['clearable'])->toBeTrue();
});

test('filters is empty when a definition does not declare any', function () {
    $definition = new class implements CrudDefinition
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
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['filters'])->toBe([]);
});
