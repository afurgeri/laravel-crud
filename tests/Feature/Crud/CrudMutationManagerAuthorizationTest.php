<?php

use Modules\Crud\CrudMutationManager;
use Tests\Feature\Crud\Fixtures\CreatesCrudTestRecordsTable;
use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordAuthorizedDefinition;

uses(CreatesCrudTestRecordsTable::class);

beforeEach(function () {
    $this->createCrudTestRecordsTable();

    CrudTestRecordAuthorizedDefinition::$authorized = true;
    CrudTestRecordAuthorizedDefinition::$createCalls = 0;
    CrudTestRecordAuthorizedDefinition::$updateCalls = 0;
    CrudTestRecordAuthorizedDefinition::$deleteCalls = 0;
});

test('it authorizes record creation when the definition opts in', function () {
    app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordAuthorizedDefinition,
        data: ['name' => 'Ada', 'email' => 'ada@example.com'],
    );

    expect(CrudTestRecordAuthorizedDefinition::$createCalls)->toBe(1);
});

test('it blocks record creation when the definition denies authorization', function () {
    CrudTestRecordAuthorizedDefinition::$authorized = false;

    app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordAuthorizedDefinition,
        data: ['name' => 'Ada', 'email' => 'ada@example.com'],
    );
})->throws(RuntimeException::class, 'Unauthorized crud mutation.');

test('it authorizes record updates when the definition opts in', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordAuthorizedDefinition,
        data: ['name' => 'Grace', 'email' => 'grace@example.com'],
    );

    expect(CrudTestRecordAuthorizedDefinition::$updateCalls)->toBe(1);
});

test('it blocks record updates when the definition denies authorization', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecordAuthorizedDefinition::$authorized = false;

    app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordAuthorizedDefinition,
        data: ['name' => 'Grace', 'email' => 'grace@example.com'],
    );
})->throws(RuntimeException::class, 'Unauthorized crud mutation.');

test('it authorizes record deletion when the definition opts in', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudMutationManager::class)->delete($record, new CrudTestRecordAuthorizedDefinition);

    expect(CrudTestRecordAuthorizedDefinition::$deleteCalls)->toBe(1);
});

test('it blocks record deletion when the definition denies authorization', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecordAuthorizedDefinition::$authorized = false;

    app(CrudMutationManager::class)->delete($record, new CrudTestRecordAuthorizedDefinition);
})->throws(RuntimeException::class, 'Unauthorized crud mutation.');
