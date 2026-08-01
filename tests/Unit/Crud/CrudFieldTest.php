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
