<?php

use Modules\Crud\CrudFilter;
use Modules\Crud\CrudFilterValues;

test('remote select values are preserved without resolving an options list', function () {
    $filter = CrudFilter::make('patient')
        ->remoteSelect('/patients/options');

    expect(app(CrudFilterValues::class)->for([$filter], ['patient' => '42']))
        ->toBe(['patient' => '42']);
});
