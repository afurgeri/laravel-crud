<?php

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudPresentation;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;
use Modules\Crud\CrudFormMode;
use Modules\Crud\CrudLayoutWidth;
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
                CrudColumn::make('id')->sortable()->fixedWidth('5rem'),
                CrudColumn::make('email_address')->hidden(),
            ];
        }

        public function fields(): array
        {
            return [
                CrudField::make('name', ['required', 'string', 'max:255'])->default('Ada'),
                CrudField::make('email', ['required', 'email'])->email()->span(6, 'md')->span(4, 'xl'),
                CrudField::make('is_active', ['required', 'boolean'])
                    ->select([
                        ['value' => false, 'label' => 'No'],
                        ['value' => true, 'label' => 'Yes'],
                    ])
                    ->default(true),
                CrudField::make('duration_minutes', ['required', 'integer'])->number(),
                CrudField::make('amount', ['required', 'decimal:2'])->decimal(),
                CrudField::make('notes', ['nullable', 'string', 'max:1000'])->textarea(),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema)->toMatchArray([
        'resource' => 'users',
        'title' => 'Users',
        'form_mode' => 'page',
        'page_width' => 'standard',
        'form_width' => 'standard',
        'description' => null,
        'empty_label' => null,
        'columns' => [
            [
                'name' => 'id',
                'label' => 'ID',
                'sortable' => true,
                'width' => '5rem',
                'min_width' => '5rem',
                'max_width' => '5rem',
                'fixed' => true,
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
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12],
                'visible_on_update' => true,
                'defaultValue' => 'Ada',
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'email'],
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12, 'md' => 6, 'xl' => 4],
                'visible_on_update' => true,
            ],
            [
                'name' => 'is_active',
                'label' => 'Is Active',
                'type' => 'select',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'boolean'],
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12],
                'visible_on_update' => true,
                'defaultValue' => true,
                'options' => [
                    ['value' => '0', 'label' => 'No'],
                    ['value' => '1', 'label' => 'Yes'],
                ],
            ],
            [
                'name' => 'duration_minutes',
                'label' => 'Duration Minutes',
                'type' => 'number',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'integer'],
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12],
                'visible_on_update' => true,
            ],
            [
                'name' => 'amount',
                'label' => 'Amount',
                'type' => 'number',
                'confirmed' => false,
                'required' => true,
                'rules' => ['required', 'decimal:2'],
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12],
                'visible_on_update' => true,
                'step' => '0.01',
            ],
            [
                'name' => 'notes',
                'label' => 'Notes',
                'type' => 'textarea',
                'confirmed' => false,
                'required' => false,
                'rules' => ['nullable', 'string', 'max:1000'],
                'clearable' => false,
                'visible' => true,
                'span' => ['base' => 12],
                'visible_on_update' => true,
            ],
        ],
    ]);

    expect(array_column($schema['columns'], 'name'))->toBe(['id']);
});

test('it serializes static combobox options in the field schema', function () {
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
                CrudField::make('user_id', ['nullable', 'string'])
                    ->combobox([
                        ['value' => 1, 'label' => 'Ada Lovelace'],
                    ])
                    ->multiple(),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['fields'][0])
        ->toMatchArray([
            'name' => 'user_id',
            'type' => 'combobox',
            'multiple' => true,
            'options' => [
                ['value' => '1', 'label' => 'Ada Lovelace'],
            ],
        ]);
});

test('it exposes remote field configuration with a field source', function () {
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
                CrudField::make('user_id')
                    ->relation('user')
                    ->remoteSelect(minChars: 3, debounce: 500, searchColumns: ['name']),
            ];
        }
    };

    $schema = app(CrudSchemaManager::class)->for($definition, 'users');

    expect($schema['fields'][0])
        ->toMatchArray([
            'name' => 'user_id',
            'type' => 'remote-select',
            'remote' => [
                'url' => url('users/options/user_id'),
                'min_chars' => 3,
                'debounce' => 500,
                'source' => 'field',
            ],
        ]);
});

test('it exposes unified crud presentation settings', function () {
    $definition = new class implements CrudDefinition, HasCrudPresentation
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

        public function formMode(): CrudFormMode
        {
            return CrudFormMode::Dialog;
        }

        public function pageWidth(): CrudLayoutWidth
        {
            return CrudLayoutWidth::Full;
        }

        public function formWidth(): CrudLayoutWidth
        {
            return CrudLayoutWidth::Wide;
        }
    };

    expect(app(CrudSchemaManager::class)->for($definition, 'users'))
        ->toMatchArray([
            'form_mode' => 'dialog',
            'page_width' => 'full',
            'form_width' => 'wide',
        ]);
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
                    'clearable' => false,
                    'visible' => true,
                    'span' => ['base' => 12],
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
                    'clearable' => false,
                    'visible' => true,
                    'span' => ['base' => 12],
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

    expect($schema['search'])->toBe([
        'enabled' => true,
        'value' => 'ada',
        'span' => ['base' => 12],
    ]);
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

    expect($schema['search'])->toBe([
        'enabled' => false,
        'value' => null,
        'span' => ['base' => 12],
    ]);
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
                CrudFilter::make('role')
                    ->combobox([1 => 'Admin'])
                    ->multiple()
                    ->relation('roles'),
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
            'span' => ['base' => 12, 'sm' => 4],
        ],
        [
            'name' => 'role',
            'label' => 'Role',
            'type' => 'combobox',
            'operator' => '=',
            'relation' => true,
            'clearable' => false,
            'range' => null,
            'value' => null,
            'span' => ['base' => 12, 'sm' => 4],
            'multiple' => true,
            'options' => [
                ['value' => '1', 'label' => 'Admin'],
            ],
        ],
    ]);
});

test('it exposes remote filter configuration without resolving options', function () {
    $definition = new class implements CrudDefinition, HasCrudFilters
    {
        public function model(): string
        {
            return Model::class;
        }

        public function title(): string
        {
            return 'Patients';
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
                CrudFilter::make('patient')
                    ->remoteSelect(minChars: 3, debounce: 500),
            ];
        }
    };

    expect(app(CrudSchemaManager::class)->for($definition, 'appointments')['filters'])
        ->toBe([
            [
                'name' => 'patient',
                'label' => 'Patient',
                'type' => 'remote-select',
                'operator' => '=',
                'relation' => false,
                'clearable' => false,
                'range' => null,
                'value' => null,
                'span' => ['base' => 12, 'sm' => 4],
                'remote' => [
                    'url' => 'http://localhost/appointments/options/patient',
                    'min_chars' => 3,
                    'debounce' => 500,
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
