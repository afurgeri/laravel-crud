<?php

use Illuminate\Validation\ValidationException;
use Modules\Crud\CrudMutationManager;
use Tests\Feature\Crud\Fixtures\CreatesCrudTestRecordsTable;
use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordAuthorizedDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordLifecycleDefinition;

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

test('it normalizes casted values before persisting records', function () {
    $record = app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordDefinition,
        data: [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'is_active' => '1',
            'duration_minutes' => '15',
        ],
    );

    expect($record->getRawOriginal('is_active'))->toBeTrue()
        ->and($record->getRawOriginal('duration_minutes'))->toBe(15);
});

test('it normalizes unchecked boolean values before persisting records', function () {
    $record = app(CrudMutationManager::class)->create(
        definition: new CrudTestRecordDefinition,
        data: [
            'name' => 'Grace',
            'email' => 'grace@example.com',
            'is_active' => '0',
            'duration_minutes' => '0',
        ],
    );

    expect($record->getRawOriginal('is_active'))->toBeFalse()
        ->and($record->getRawOriginal('duration_minutes'))->toBe(0);
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

test('it runs mutation hooks around each persisted operation', function () {
    CrudTestRecordLifecycleDefinition::$events = [];
    CrudTestRecordLifecycleDefinition::$lastData = [];
    $manager = app(CrudMutationManager::class);
    $definition = new CrudTestRecordLifecycleDefinition;

    $record = $manager->create($definition, [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'specialties' => ['specialty-id'],
    ]);
    $manager->update($record, $definition, [
        'name' => 'Grace',
        'email' => 'grace@example.com',
    ]);
    $manager->delete($record, $definition);

    expect(CrudTestRecordLifecycleDefinition::$events)->toBe([
        'beforeCreate',
        'afterCreate',
        'beforeUpdate',
        'afterUpdate',
        'beforeDelete',
        'afterDelete',
    ])
        ->and(CrudTestRecordLifecycleDefinition::$lastData)->toBe([
            'name' => 'Grace',
            'email' => 'grace@example.com',
        ]);
});

test('it does not run a delete hook when authorization fails', function () {
    CrudTestRecordLifecycleDefinition::$events = [];
    CrudTestRecordAuthorizedDefinition::$authorized = false;
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudMutationManager::class)->delete($record, new CrudTestRecordLifecycleDefinition);
})->throws(RuntimeException::class, 'Unauthorized crud mutation.');
