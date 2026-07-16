<?php

use Illuminate\Support\Facades\DB;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudFilter;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\Exceptions\InvalidCrudFilterRange;
use Modules\Crud\Exceptions\InvalidCrudFilterValue;
use Modules\Crud\Exceptions\InvalidCrudSortColumn;
use Tests\Feature\Crud\Fixtures\CreatesCrudTestRecordsTable;
use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordComputedColumnDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefaultFilterDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefaultSortDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordFilterableDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordIndexAuthorizedDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordNote;
use Tests\Feature\Crud\Fixtures\CrudTestRecordSearchableDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordWithNotesDefinition;

uses(CreatesCrudTestRecordsTable::class);

beforeEach(function () {
    $this->createCrudTestRecordsTable();

    CrudTestRecordIndexAuthorizedDefinition::$authorized = true;
    CrudTestRecordIndexAuthorizedDefinition::$viewAnyCalls = 0;
});

test('it paginates records for a crud definition', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefinition,
        perPage: 1,
    );

    expect($paginator->total())->toBe(2)
        ->and($paginator->perPage())->toBe(1)
        ->and($paginator->items())->toHaveCount(1);
});

test('it selects only visible columns', function () {
    CrudTestRecord::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'internal_notes' => 'Hidden from the CRUD index',
    ]);

    $paginator = app(CrudIndexManager::class)->paginate(new CrudTestRecordDefinition);
    $record = $paginator->items()[0];

    expect(array_keys($record->getAttributes()))->toEqualCanonicalizing(['id', 'name', 'email']);
});

test('it sorts by allowed sortable columns', function () {
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefinition,
        sort: 'name',
        direction: 'asc',
    );

    expect($paginator->items()[0]->name)->toBe('Ada')
        ->and($paginator->items()[1]->name)->toBe('Grace');
});

test('it rejects sorting by non sortable columns', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefinition,
        sort: 'email',
    );
})->throws(InvalidCrudSortColumn::class);

test('it paginates without querying computed columns', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(new CrudTestRecordComputedColumnDefinition);
    $record = $paginator->items()[0];

    expect(array_keys($record->getAttributes()))->not->toContain('tag_count');
});

test('it authorizes viewing the index when the definition opts in', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(new CrudTestRecordIndexAuthorizedDefinition);

    expect(CrudTestRecordIndexAuthorizedDefinition::$viewAnyCalls)->toBe(1)
        ->and($paginator->total())->toBe(1);
});

test('it blocks viewing the index when the definition denies authorization', function () {
    CrudTestRecordIndexAuthorizedDefinition::$authorized = false;

    app(CrudIndexManager::class)->paginate(new CrudTestRecordIndexAuthorizedDefinition);
})->throws(RuntimeException::class, 'Unauthorized crud index view.');

test('paginate orders by the definition default sort when no sort is requested', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(new CrudTestRecordDefaultSortDefinition);

    expect($paginator->items()[0]->name)->toBe('Grace')
        ->and($paginator->items()[1]->name)->toBe('Ada');
});

test('paginate does not force ordering for definitions without a default sort', function () {
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    DB::enableQueryLog();

    app(CrudIndexManager::class)->paginate(new CrudTestRecordDefinition);

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->contains(fn (string $query): bool => str_contains(strtolower($query), 'order by')))
        ->toBeFalse();
});

test('it eager loads relations declared by crud definitions', function () {
    $record = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecordNote::query()->create([
        'crud_test_record_id' => $record->id,
        'body' => 'First note',
    ]);

    $paginator = app(CrudIndexManager::class)->paginate(new CrudTestRecordWithNotesDefinition);
    $result = $paginator->items()[0];

    expect($result->relationLoaded('notes'))->toBeTrue()
        ->and($result->notes)->toHaveCount(1)
        ->and($result->notes->first()->body)->toBe('First note');
});

test('it searches across searchable columns', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordSearchableDefinition,
        search: 'ada',
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('search matches any searchable column, not just the first one', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordSearchableDefinition,
        search: 'grace@example.com',
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Grace');
});

test('search is a no-op when the definition has no searchable columns', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefinition,
        search: 'ada',
    );

    expect($paginator->total())->toBe(2);
});

test('it filters by a declared text filter', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['name' => 'ada'],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('it filters a date column with the operator configured on the filter', function () {
    $old = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $old->forceFill(['created_at' => now()->subDays(10)])->save();

    $inRange = CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);
    $inRange->forceFill(['created_at' => now()->subDays(1)])->save();

    $future = CrudTestRecord::query()->create(['name' => 'Rosalind', 'email' => 'rosalind@example.com']);
    $future->forceFill(['created_at' => now()->addDays(5)])->save();

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: [
            'created_from' => now()->subDays(3)->toDateString(),
            'created_to' => now()->toDateString(),
        ],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Grace');
});

test('a date "to" filter includes the entire end date regardless of time of day', function () {
    $lastMinuteOfDay = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $lastMinuteOfDay->forceFill(['created_at' => '2026-01-31 23:59:59'])->save();

    $nextDay = CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);
    $nextDay->forceFill(['created_at' => '2026-02-01 00:00:01'])->save();

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['created_to' => '2026-01-31'],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('it rejects a range where the lower bound is after the upper bound', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: [
            'created_from' => now()->toDateString(),
            'created_to' => now()->subDays(3)->toDateString(),
        ],
    );
})->throws(InvalidCrudFilterRange::class);

test('a range is not validated unless both bounds are submitted', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['created_from' => now()->addDays(30)->toDateString()],
    );

    expect($paginator->total())->toBe(0);
});

test('it rejects a date filter value after the filter\'s configured maximum date', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['joined_before' => now()->addDays(5)->toDateString()],
    );
})->throws(InvalidCrudFilterValue::class);

test('it accepts a date filter value at or before the filter\'s configured maximum date', function () {
    $inRange = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $inRange->forceFill(['created_at' => now()->subDays(1)])->save();

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['joined_before' => now()->toDateString()],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('a filter\'s default value is applied when no value is submitted', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefaultFilterDefinition,
        filters: [],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('an explicitly submitted value overrides a filter\'s default', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefaultFilterDefinition,
        filters: ['name' => 'grace'],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Grace');
});

test('clearing a filter (submitting an empty string) falls back to its default', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordDefaultFilterDefinition,
        filters: ['name' => ''],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('it ignores a selected option that is no longer available for cascading filters', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@test.com']);

    $definition = new class extends CrudTestRecordDefinition implements HasCrudFilters
    {
        public function filters(): array
        {
            return [
                CrudFilter::make('email_scope', 'email')->text()->default('example.com'),
                CrudFilter::make('name_choice', 'name')->select(
                    fn (array $filterValues): array => ($filterValues['email_scope'] ?? null) === 'example.com'
                        ? ['Ada' => 'Ada']
                        : ['Grace' => 'Grace']
                ),
            ];
        }
    };

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: $definition,
        filters: ['name_choice' => 'Grace'],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('it filters a number column with the operator configured on the filter', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $second = CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['min_id' => $second->id],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Grace');
});

test('it filters through a relation via whereHas', function () {
    $withNote = CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $note = CrudTestRecordNote::query()->create([
        'crud_test_record_id' => $withNote->id,
        'body' => 'Hello',
    ]);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['note' => $note->id],
    );

    expect($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]->name)->toBe('Ada');
});

test('unknown filter names are ignored', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $paginator = app(CrudIndexManager::class)->paginate(
        definition: new CrudTestRecordFilterableDefinition,
        filters: ['bogus' => 'anything'],
    );

    expect($paginator->total())->toBe(2);
});
