<?php

use Modules\Crud\CrudFilter;
use Modules\Crud\CrudFilterValues;

test('remote select values are preserved without resolving an options list', function () {
    $filter = CrudFilter::make('patient')
        ->remoteSelect('/patients/options');

    expect(app(CrudFilterValues::class)->for([$filter], ['patient' => '42']))
        ->toBe(['patient' => '42']);
});

test('multiple filters keep only selected values that exist in their options', function () {
    $filter = CrudFilter::make('status')
        ->select([1 => 'Active', 2 => 'Inactive'])
        ->multiple();

    expect(app(CrudFilterValues::class)->for([$filter], [
        'status' => ['1', 'unknown', 2],
    ]))->toBe(['status' => ['1', '2']]);
});
