<?php

use Illuminate\Validation\ValidationException;
use Modules\Crud\CrudMutationManager;
use Tests\Feature\Crud\Fixtures\CreatesCrudTestRecordsTable;
use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefinition;

uses(CreatesCrudTestRecordsTable::class);

beforeEach(function () {
    $this->createCrudTestRecordsTable();
});

test('it creates records using configured fields only', function () {
    $record = app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordDefinition,
        data: [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'internal_notes' => 'This field is not editable through the CRUD definition.',
        ],
    );

    expect($record)->toBeInstanceOf(CrudTestRecord::class)
        ->and($record->exists)->toBeTrue()
        ->and($record->name)->toBe('Ada')
        ->and($record->email)->toBe('ada@example.com')
        ->and($record->internal_notes)->toBeNull();
});

test('it validates data before creating records', function () {
    app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordDefinition,
        data: ['name' => '', 'email' => 'not-an-email'],
    );
})->throws(ValidationException::class);

test('it rejects duplicate unique fields when creating records', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordDefinition,
        data: ['name' => 'Grace', 'email' => 'ada@example.com'],
    );
})->throws(ValidationException::class);

test('it updates records using configured fields only', function () {
    $record = CrudTestRecord::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'internal_notes' => 'Preserved',
    ]);

    $updated = app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordDefinition,
        data: [
            'name' => 'Grace',
            'email' => 'grace@example.com',
            'internal_notes' => 'Should not change',
        ],
    );

    expect($updated->name)->toBe('Grace')
        ->and($updated->email)->toBe('grace@example.com')
        ->and($updated->internal_notes)->toBe('Preserved');
});

test('it validates data before updating records', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordDefinition,
        data: ['name' => '', 'email' => 'not-an-email'],
    );
})->throws(ValidationException::class);

test('it allows unique fields to keep their current value when updating records', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $updated = app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordDefinition,
        data: ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
    );

    expect($updated->email)->toBe('ada@example.com');
});

test('it rejects duplicate unique fields when updating records', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $record = CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    app(CrudMutationManager::class)->update(
        model: $record,
        definition: new CrudTestRecordDefinition,
        data: ['name' => 'Grace Hopper', 'email' => 'ada@example.com'],
    );
})->throws(ValidationException::class);

test('it deletes records', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $deleted = app(CrudMutationManager::class)->delete($record, new CrudTestRecordDefinition);

    expect($deleted)->toBeTrue()
        ->and(CrudTestRecord::query()->exists())->toBeFalse();
});
