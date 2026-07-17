<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\CrudMutationManager;
use Tests\MongoDB\Fixtures\MongoCrudTestRecord;
use Tests\MongoDB\Fixtures\MongoCrudTestRecordDefinition;
use Tests\MongoDB\Fixtures\MongoCrudTestRecordNote;
use Tests\MongoDbTestCase;

uses(MongoDbTestCase::class);

beforeEach(function () {
    MongoCrudTestRecordNote::query()->delete();
    MongoCrudTestRecord::query()->delete();

    DB::connection('mongodb')
        ->getCollection('crud_mongo_test_records')
        ->createIndex(['email' => 1], ['unique' => true]);
});

afterEach(function () {
    MongoCrudTestRecordNote::query()->delete();
    MongoCrudTestRecord::query()->delete();
});

test('it executes the CRUD mutation lifecycle against MongoDB', function () {
    $manager = app(CrudMutationManager::class);

    $record = $manager->create(new MongoCrudTestRecordDefinition, [
        'name' => 'Ada',
        'email' => 'ada@example.com',
    ]);

    expect($record)->toBeInstanceOf(MongoCrudTestRecord::class)
        ->and($record->getKey())->not->toBeNull()
        ->and($record->id)->toBeString();

    $updated = $manager->update($record, new MongoCrudTestRecordDefinition, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    expect($updated->name)->toBe('Ada Lovelace')
        ->and($updated->fresh()->name)->toBe('Ada Lovelace');

    expect($manager->delete($updated, new MongoCrudTestRecordDefinition))->toBeTrue()
        ->and(MongoCrudTestRecord::query()->count())->toBe(0);
});

test('it supports MongoDB pagination projection sorting search and filters', function () {
    $old = MongoCrudTestRecord::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'internal_notes' => 'hidden',
        'created_at' => CarbonImmutable::parse('2026-01-01 12:00:00'),
    ]);

    MongoCrudTestRecord::query()->create([
        'name' => 'Grace',
        'email' => 'grace@example.com',
        'created_at' => CarbonImmutable::parse('2026-02-01 12:00:00'),
    ]);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new MongoCrudTestRecordDefinition,
        perPage: 1,
        sort: 'name',
        direction: 'desc',
        search: 'example.com',
        filters: [
            'created_from' => '2026-01-01',
            'created_to' => '2026-01-31',
        ],
    );

    expect($paginator->total())->toBe(1);

    $record = $paginator->items()[0];

    expect($record->name)->toBe('Ada')
        ->and(array_keys($record->getAttributes()))->toEqualCanonicalizing(['id', 'name', 'email', 'created_at'])
        ->and($old->fresh()->internal_notes)->toBe('hidden');
});

test('it eager loads and filters through MongoDB relationships', function () {
    $record = MongoCrudTestRecord::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
    ]);

    MongoCrudTestRecordNote::query()->create([
        'mongo_crud_test_record_id' => $record->getKey(),
        'body' => 'First note',
    ]);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new MongoCrudTestRecordDefinition,
        filters: ['note' => 'First note'],
    );

    expect($paginator->total())->toBe(1)
        ->and($paginator->items()[0]->relationLoaded('notes'))->toBeTrue()
        ->and($paginator->items()[0]->notes)->toHaveCount(1);
});

test('it validates unique MongoDB fields through the CRUD manager', function () {
    MongoCrudTestRecord::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
    ]);

    app(CrudMutationManager::class)->create(new MongoCrudTestRecordDefinition, [
        'name' => 'Grace',
        'email' => 'ada@example.com',
    ]);
})->throws(ValidationException::class);
