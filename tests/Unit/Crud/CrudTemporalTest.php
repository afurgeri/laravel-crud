<?php

use Modules\Crud\CrudTemporal;

test('crud datetime values are normalized from Buenos Aires to UTC', function () {
    config()->set('app.timezone', 'America/Argentina/Buenos_Aires');

    expect(CrudTemporal::normalizeDateTime('2026-08-08T15:00'))
        ->toBe('2026-08-08 18:00:00');
});

test('crud datetime values are serialized with the configured application timezone', function () {
    config()->set('app.timezone', 'America/Argentina/Buenos_Aires');

    expect(CrudTemporal::serializeDateTime('2026-08-08 18:00:00'))
        ->toBe('2026-08-08T15:00:00-03:00');
});

test('crud temporal values keep empty values unchanged', function () {
    expect(CrudTemporal::normalizeDateTime(''))->toBe('')
        ->and(CrudTemporal::normalizeDateTime(null))->toBeNull();
});
