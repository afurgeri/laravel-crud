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
