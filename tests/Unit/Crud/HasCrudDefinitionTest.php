<?php

use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefinition;

test('models can resolve their crud definition through the trait bridge', function () {
    expect(CrudTestRecord::crudDefinition())->toBe(CrudTestRecordDefinition::class)
        ->and(CrudTestRecord::makeCrudDefinition())->toBeInstanceOf(CrudTestRecordDefinition::class);
});
