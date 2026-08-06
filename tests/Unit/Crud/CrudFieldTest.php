<?php

use Modules\Crud\CrudField;

test('crud fields expose their name and validation rules', function () {
    $field = CrudField::make('name', ['required', 'string']);

    expect($field->name())->toBe('name')
        ->and($field->validationRules())->toBe(['required', 'string']);
});

test('crud fields can configure rules fluently', function () {
    $field = CrudField::make('email')->rules(['required', 'email']);

    expect($field->validationRules())->toBe(['required', 'email']);
});

test('crud fields can require unique values', function () {
    $field = CrudField::make('email')->unique('email_address');

    expect($field->isUnique())->toBeTrue()
        ->and($field->uniqueColumn())->toBe('email_address');
});

test('crud fields can be visible only when creating', function () {
    $field = CrudField::make('password')->createOnly();

    expect($field->isVisibleOnUpdate())->toBeFalse();
});

test('crud fields can be hidden from automatic form rendering', function () {
    $field = CrudField::make('user_id')->hidden();

    expect($field->isVisible())->toBeFalse();
});

test('crud fields configure responsive column spans', function () {
    $field = CrudField::make('email')
        ->span(6, 'md')
        ->span(4, 'xl');

    expect($field->spans())->toBe([
        'base' => 12,
        'md' => 6,
        'xl' => 4,
    ]);
});

test('crud fields reject invalid responsive column spans', function (int $columns) {
    CrudField::make('email')->span($columns);
})->with([0, 13])->throws(InvalidArgumentException::class);

test('crud fields reject unsupported responsive span breakpoints', function () {
    CrudField::make('email')->span(6, 'tablet');
})->throws(InvalidArgumentException::class);

test('crud fields can define a translation label key', function () {
    $field = CrudField::make('email')->label('Email address');

    expect($field->labelKey())->toBe('Email address');
});

test('crud fields can define default values', function () {
    $field = CrudField::make('is_active')->default(false);

    expect($field->hasDefault())->toBeTrue()
        ->and($field->defaultValue())->toBeFalse();
});

test('crud fields configure visual input types explicitly', function () {
    expect(CrudField::make('is_active', ['required', 'boolean'])->checkbox()->type())->toBe('checkbox')
        ->and(CrudField::make('duration_minutes', ['required', 'integer'])->number()->type())->toBe('number')
        ->and(CrudField::make('starts_on', ['nullable', 'date'])->date()->type())->toBe('date')
        ->and(CrudField::make('notes', ['nullable', 'string'])->textarea()->type())->toBe('textarea')
        ->and(CrudField::make('name', ['required', 'string'])->type())->toBe('text');
});

test('crud fields allow explicit input type overrides', function () {
    expect(CrudField::make('is_active', ['boolean'])->number()->type())->toBe('number');
});

test('crud fields configure decimal input precision', function () {
    expect(CrudField::make('amount', ['required', 'decimal:2'])->decimal()->type())
        ->toBe('number')
        ->and(CrudField::make('amount')->decimal()->step())->toBe('0.01')
        ->and(CrudField::make('amount')->decimal(3)->step())->toBe('0.001');
});

test('crud fields reject invalid decimal precision', function () {
    CrudField::make('amount')->decimal(0);
})->throws(InvalidArgumentException::class);

test('crud fields can configure static select options', function () {
    $field = CrudField::make('is_active', ['required', 'boolean'])->select([
        ['value' => false, 'label' => 'No'],
        ['value' => true, 'label' => 'Yes'],
    ]);

    expect($field->type())->toBe('select')
        ->and($field->options())->toBe([
            ['value' => false, 'label' => 'No'],
            ['value' => true, 'label' => 'Yes'],
        ]);
});

test('crud fields can configure static combobox options', function () {
    $field = CrudField::make('user_id', ['nullable', 'string'])->combobox([
        ['value' => 1, 'label' => 'Ada Lovelace'],
        ['value' => 2, 'label' => 'Grace Hopper'],
    ]);

    expect($field->type())->toBe('combobox')
        ->and($field->options())->toBe([
            ['value' => 1, 'label' => 'Ada Lovelace'],
            ['value' => 2, 'label' => 'Grace Hopper'],
        ]);
});

test('crud fields can configure remote selects', function () {
    $field = CrudField::make('user_id', ['nullable', 'string'])
        ->relation('user', 'uuid')
        ->remoteSelect('/users/options/user_id', 3, 500, ['name', 'email']);

    expect($field->type())->toBe('remote-select')
        ->and($field->isRemote())->toBeTrue()
        ->and($field->isRelation())->toBeTrue()
        ->and($field->relationName())->toBe('user')
        ->and($field->relationColumn())->toBe('uuid')
        ->and($field->remoteConfig())->toBe([
            'url' => '/users/options/user_id',
            'min_chars' => 3,
            'debounce' => 500,
        ])
        ->and($field->remoteSearchColumns())->toBe(['name', 'email']);
});
