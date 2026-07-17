<?php

use Modules\Crud\CrudIndexManager;
use Tests\MongoDB\Fixtures\MongoCitizen;
use Tests\MongoDB\Fixtures\MongoCity;
use Tests\MongoDB\Fixtures\MongoCityCrudDefinition;
use Tests\MongoDbTestCase;

uses(MongoDbTestCase::class);

beforeEach(function () {
    MongoCitizen::query()->delete();
    MongoCity::query()->delete();
});

afterEach(function () {
    MongoCitizen::query()->delete();
    MongoCity::query()->delete();
});

test('it loads referenced cities and citizens in both directions', function () {
    $city = MongoCity::query()->create(['name' => 'Buenos Aires']);
    $citizen = MongoCitizen::query()->create([
        'name' => 'Ada',
        'city_id' => $city->getKey(),
    ]);

    $city->load('citizens');
    $citizen->load('city');

    expect($city->citizens)->toHaveCount(1)
        ->and((string) $city->citizens->first()->getKey())->toBe((string) $citizen->getKey())
        ->and($citizen->city->name)->toBe('Buenos Aires');
});

test('it eager loads and filters referenced citizens through the CRUD manager', function () {
    $buenosAires = MongoCity::query()->create(['name' => 'Buenos Aires']);
    $cordoba = MongoCity::query()->create(['name' => 'Cordoba']);

    MongoCitizen::query()->create([
        'name' => 'Ada',
        'city_id' => $buenosAires->getKey(),
    ]);
    MongoCitizen::query()->create([
        'name' => 'Grace',
        'city_id' => $cordoba->getKey(),
    ]);

    $relationQuery = MongoCity::query()
        ->whereHas('citizens', fn ($query) => $query->where('name', 'Ada'));

    expect($relationQuery->count())->toBe(1);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new MongoCityCrudDefinition,
        filters: ['citizen' => 'Ada'],
    );

    expect($paginator->total())->toBe(1)
        ->and($paginator->items()[0]->name)->toBe('Buenos Aires')
        ->and($paginator->items()[0]->relationLoaded('citizens'))->toBeTrue()
        ->and($paginator->items()[0]->citizens->first()->name)->toBe('Ada');
});
