<?php

use Modules\Crud\CrudColumn;

test('crud columns are visible by default', function () {
    $column = CrudColumn::make('name');

    expect($column->name())->toBe('name')
        ->and($column->isVisible())->toBeTrue()
        ->and($column->isSortable())->toBeFalse()
        ->and($column->isSearchable())->toBeFalse()
        ->and($column->isComputed())->toBeFalse();
});

test('crud columns can be configured fluently', function () {
    $column = CrudColumn::make('email')
        ->hidden()
        ->sortable()
        ->searchable();

    expect($column->name())->toBe('email')
        ->and($column->isVisible())->toBeFalse()
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

test('crud columns can be marked as computed', function () {
    $column = CrudColumn::make('permission_ids')->computed();

    expect($column->isComputed())->toBeTrue();
});

test('crud columns can define a translation label key', function () {
    $column = CrudColumn::make('email')->label('Email address');

    expect($column->labelKey())->toBe('Email address');
});

test('crud columns can define width constraints', function () {
    $column = CrudColumn::make('email')
        ->width('18rem')
        ->minWidth('12rem')
        ->maxWidth('24rem');

    expect($column->widthValue())->toBe('18rem')
        ->and($column->minWidthValue())->toBe('12rem')
        ->and($column->maxWidthValue())->toBe('24rem');
});

test('crud columns can define a fixed width', function () {
    $column = CrudColumn::make('id')->fixedWidth('5rem');

    expect($column->widthValue())->toBe('5rem')
        ->and($column->minWidthValue())->toBe('5rem')
        ->and($column->maxWidthValue())->toBe('5rem')
        ->and($column->hasFixedWidth())->toBeTrue();
});
