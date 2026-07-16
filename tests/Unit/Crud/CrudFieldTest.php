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
