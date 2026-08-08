<?php

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\CrudFilter;

test('crud filters default to a plain text filter with no relation', function () {
    $filter = CrudFilter::make('name');

    expect($filter->name())->toBe('name')
        ->and($filter->column())->toBe('name')
        ->and($filter->type())->toBe('text')
        ->and($filter->comparisonOperator())->toBe('=')
        ->and($filter->isRelation())->toBeFalse()
        ->and($filter->relationName())->toBeNull()
        ->and($filter->relationColumn())->toBe('id')
        ->and($filter->resolvedOptions())->toBe([]);
});

test('crud filters can use a different column than their name', function () {
    $filter = CrudFilter::make('created_from', 'created_at');

    expect($filter->name())->toBe('created_from')
        ->and($filter->column())->toBe('created_at');
});

test('crud filters can be configured as a date filter', function () {
    $filter = CrudFilter::make('created_at')->date();

    expect($filter->type())->toBe('date');
});

test('crud filters support date, time, and datetime values', function () {
    expect(CrudFilter::make('starts_on')->date()->type())->toBe('date')
        ->and(CrudFilter::make('starts_at')->time()->type())->toBe('time')
        ->and(CrudFilter::make('starts_on')->datetime()->type())->toBe('datetime');
});

test('crud filters can be configured as a number filter', function () {
    $filter = CrudFilter::make('age')->number();

    expect($filter->type())->toBe('number');
});

test('crud filters can be configured with a comparison operator', function () {
    $filter = CrudFilter::make('created_from', 'created_at')->date()->operator('>=');

    expect($filter->comparisonOperator())->toBe('>=');
});

test('crud filters fall back to equals for an unsupported operator', function () {
    $filter = CrudFilter::make('age')->number()->operator('<>');

    expect($filter->comparisonOperator())->toBe('=');
});

test('crud filters can be configured as a select filter with an options array', function () {
    $filter = CrudFilter::make('status')->select(['active' => 'Active', 'inactive' => 'Inactive']);

    expect($filter->type())->toBe('select')
        ->and($filter->resolvedOptions())->toBe(['active' => 'Active', 'inactive' => 'Inactive']);
});

test('crud filters can be configured as a searchable combobox with an options array', function () {
    $filter = CrudFilter::make('status')->combobox(['active' => 'Active', 'inactive' => 'Inactive']);

    expect($filter->type())->toBe('combobox')
        ->and($filter->resolvedOptions())->toBe(['active' => 'Active', 'inactive' => 'Inactive']);
});

test('crud filters can enable multiple local options', function () {
    $filter = CrudFilter::make('status')->combobox(['active' => 'Active'])->multiple();

    expect($filter->isMultiple())->toBeTrue();
});

test('crud filters reject multiple options for unsupported filter types', function () {
    CrudFilter::make('name')->multiple();
})->throws(InvalidArgumentException::class);

test('crud filters reset multiple options when changing to an unsupported filter type', function () {
    $filter = CrudFilter::make('roles')
        ->select([1 => 'Admin'])
        ->multiple()
        ->remoteSelect();

    expect($filter->isMultiple())->toBeFalse();
});

test('crud filters can be configured as remote selects', function () {
    $filter = CrudFilter::make('patient')
        ->remoteSelect('/patients/options', 3, 500);

    expect($filter->type())->toBe('remote-select')
        ->and($filter->isRemote())->toBeTrue()
        ->and($filter->remoteConfig())->toBe([
            'url' => '/patients/options',
            'min_chars' => 3,
            'debounce' => 500,
        ]);
});

test('crud filters can define remote search columns and labels without a URL', function () {
    $filter = CrudFilter::make('patient')
        ->relation('patient')
        ->remoteSelect(
            searchColumns: ['first_name', 'last_name', 'identification_number'],
            label: fn (Model $patient): string => (string) $patient->getAttribute('name'),
        );

    expect($filter->remoteConfig('/patients/options/patient')['url'])
        ->toBe('/patients/options/patient')
        ->and($filter->remoteSearchColumns())
        ->toBe(['first_name', 'last_name', 'identification_number']);
});

test('crud filters resolve select options from a closure lazily', function () {
    $calls = 0;

    $filter = CrudFilter::make('role')->select(function () use (&$calls): array {
        $calls++;

        return [1 => 'Admin'];
    });

    expect($calls)->toBe(0);
    expect($filter->resolvedOptions())->toBe([1 => 'Admin']);
    expect($calls)->toBe(1);
});

test('crud filters pass the current filter values to the options closure', function () {
    $received = null;

    $filter = CrudFilter::make('member')->select(function (array $filterValues) use (&$received): array {
        $received = $filterValues;

        return [10 => 'Ada'];
    });

    expect($filter->resolvedOptions(['role' => '5']))->toBe([10 => 'Ada'])
        ->and($received)->toBe(['role' => '5']);
});

test('crud filters still resolve zero-argument option closures when values are provided', function () {
    $filter = CrudFilter::make('role')->select(fn (): array => [1 => 'Admin']);

    expect($filter->resolvedOptions(['name' => 'ada']))->toBe([1 => 'Admin']);
});

test('crud filters can be configured to filter through a relation', function () {
    $filter = CrudFilter::make('role')->relation('roles', 'id');

    expect($filter->isRelation())->toBeTrue()
        ->and($filter->relationName())->toBe('roles')
        ->and($filter->relationColumn())->toBe('id');
});

test('crud filters have no range group by default', function () {
    $filter = CrudFilter::make('created_from', 'created_at')->date()->operator('>=');

    expect($filter->rangeGroup())->toBeNull();
});

test('crud filters can be tagged as part of a range group', function () {
    $filter = CrudFilter::make('created_from', 'created_at')->date()->operator('>=')->range('created_at');

    expect($filter->rangeGroup())->toBe('created_at');
});

test('crud filters configure responsive column spans', function () {
    $filter = CrudFilter::make('name')->span(6, 'md')->span(4, 'xl');

    expect($filter->spans())->toBe([
        'base' => 12,
        'md' => 6,
        'xl' => 4,
    ]);
});

test('crud filters have no maximum date by default', function () {
    $filter = CrudFilter::make('created_at')->date();

    expect($filter->resolvedMaxDate())->toBeNull();
});

test('crud filters can be configured with a fixed maximum date', function () {
    $filter = CrudFilter::make('created_at')->date()->maxDate('2026-12-31');

    expect($filter->resolvedMaxDate())->toBe('2026-12-31');
});

test('crud filters resolve a closure maximum date lazily', function () {
    $calls = 0;

    $filter = CrudFilter::make('created_at')->date()->maxDate(function () use (&$calls): string {
        $calls++;

        return '2026-01-01';
    });

    expect($calls)->toBe(0);
    expect($filter->resolvedMaxDate())->toBe('2026-01-01');
    expect($calls)->toBe(1);
});

test('crud filters have no default value by default', function () {
    $filter = CrudFilter::make('name')->text();

    expect($filter->resolvedDefault())->toBeNull();
});

test('crud filters can be configured with a fixed default value', function () {
    $filter = CrudFilter::make('name')->text()->default('Ada');

    expect($filter->resolvedDefault())->toBe('Ada');
});

test('crud filters resolve a closure default value lazily', function () {
    $calls = 0;

    $filter = CrudFilter::make('created_at')->date()->default(function () use (&$calls): string {
        $calls++;

        return '2026-01-01';
    });

    expect($calls)->toBe(0);
    expect($filter->resolvedDefault())->toBe('2026-01-01');
    expect($calls)->toBe(1);
});

test('crud filters are not clearable by default', function () {
    $filter = CrudFilter::make('name')->text();

    expect($filter->isClearable())->toBeFalse();
});

test('crud filters can be marked as clearable', function () {
    $filter = CrudFilter::make('role')->select(['1' => 'Admin'])->clearable();

    expect($filter->isClearable())->toBeTrue();
});

test('crud filters can define a translation label key without changing their technical name', function () {
    $filter = CrudFilter::make('created_from', 'created_at')->label('created_from');

    expect($filter->name())->toBe('created_from')
        ->and($filter->labelKey())->toBe('created_from');
});
