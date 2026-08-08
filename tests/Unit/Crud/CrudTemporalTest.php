<?php

use Modules\Crud\CrudTemporal;

test('crud datetime values are normalized from Buenos Aires to UTC', function () {
    expect(CrudTemporal::normalizeDateTime('2026-08-08T15:00'))
        ->toBe('2026-08-08 18:00:00');
});

test('crud temporal values keep empty values unchanged', function () {
    expect(CrudTemporal::normalizeDateTime(''))->toBe('')
        ->and(CrudTemporal::normalizeDateTime(null))->toBeNull();
});
