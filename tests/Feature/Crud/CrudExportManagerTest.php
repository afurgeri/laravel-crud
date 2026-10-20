<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Modules\Crud\Concerns\HandlesCrudPaginationHooks;
use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudExport;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudOperations;
use Modules\Crud\Contracts\HasCrudPaginationHooks;
use Modules\Crud\CrudExportColumn;
use Modules\Crud\CrudExportManager;
use Modules\Crud\CrudFilter;
use Modules\Crud\CrudOperation;
use Modules\Crud\CrudSchemaManager;
use Modules\Crud\Exceptions\InvalidCrudSortColumn;
use Tests\Feature\Crud\Fixtures\CreatesCrudTestRecordsTable;
use Tests\Feature\Crud\Fixtures\CrudTestRecord;
use Tests\Feature\Crud\Fixtures\CrudTestRecordDefinition;
use Tests\Feature\Crud\Fixtures\CrudTestRecordSearchableDefinition;

uses(CreatesCrudTestRecordsTable::class);

function csvTestDefinition(): CrudTestRecordSearchableDefinition
{
    return new class extends CrudTestRecordSearchableDefinition implements EagerLoadsCrudRelations, HasCrudExport, HasCrudFilters
    {
        public function exportColumns(): array
        {
            return [
                CrudExportColumn::make('email')->header('Correo'),
                CrudExportColumn::make('name')->header('Nombre'),
                CrudExportColumn::make('notes')->header('Notas')->valueUsing(function (Model $record): string {
                    expect($record->relationLoaded('notes'))->toBeTrue();

                    return $record->getRelation('notes')->pluck('body')->implode(';');
                }),
                CrudExportColumn::make('name')->header('Mayúsculas')
                    ->formatUsing(fn ($value, Model $record): string => mb_strtoupper($value).':'.$record->id),
            ];
        }

        public function eagerLoads(): array
        {
            return ['notes'];
        }

        public function filters(): array
        {
            return [CrudFilter::make('name')->text()];
        }
    };
}

beforeEach(function () {
    $this->createCrudTestRecordsTable();
    $this->actingAs(new GenericUser(['id' => 1]));
    Gate::define('export', fn () => true);
});

test('csv preserves delimiters quotes newlines accents and column order', function () {
    $record = CrudTestRecord::query()->create([
        'name' => "José; \"Premium\"\nSegunda línea",
        'email' => 'jose@example.com',
        'internal_notes' => 'DO NOT EXPORT',
    ]);
    $record->notes()->create(['body' => 'Piezas; accesorios']);

    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download(csvTestDefinition()));

    $response->assertDownload()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertStreamedContent(
        "\xEF\xBB\xBFCorreo;Nombre;Notas;Mayúsculas\r\n".
        'jose@example.com;"José; ""Premium""'."\n".'Segunda línea";"Piezas; accesorios";"JOSÉ; ""PREMIUM""'."\n".'SEGUNDA LÍNEA:'.$record->id.'"'."\r\n"
    );
});

test('csv protects text and headings against formulas but preserves numeric values', function (string $text) {
    CrudTestRecord::query()->create(['name' => $text, 'email' => 'test@example.com']);
    $definition = new class extends CrudTestRecordDefinition implements HasCrudExport
    {
        public function exportColumns(): array
        {
            return [
                CrudExportColumn::make('name')->header('=HEADER'),
                CrudExportColumn::make('number')->valueUsing(fn (): int => -42),
                CrudExportColumn::make('empty')->valueUsing(fn () => null),
                CrudExportColumn::make('boolean')->valueUsing(fn (): bool => false),
            ];
        }
    };

    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download($definition));
    $stream = fopen('php://memory', 'w+');
    fwrite($stream, substr($response->streamedContent(), 3));
    rewind($stream);
    $header = fgetcsv($stream, escape: '', separator: ';');
    $row = fgetcsv($stream, escape: '', separator: ';');
    fclose($stream);

    expect($header)->toBe(["'=HEADER", 'Number', 'Empty', 'Boolean'])
        ->and($row)->toBe(["'".$text, '-42', '', '0']);
})->with(['=SUM(A1)', '+CMD', '-CMD', '@SUM(A1)', "\tformula", "\rformula", "\nformula", '  =SUM(A1)']);

test('csv exports all searched filtered scoped results in order across batches', function () {
    $records = [];
    for ($i = 0; $i < 505; $i++) {
        $records[] = ['name' => sprintf('Match %03d', $i), 'email' => 'match@example.com'];
    }
    $records[] = ['name' => 'Other', 'email' => 'other@example.com'];
    $records[] = ['name' => 'Match hidden', 'email' => 'hidden@example.com'];
    CrudTestRecord::query()->insert($records);

    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download(
        definition: csvTestDefinition(), sort: 'name', direction: 'desc', search: 'Match',
        filters: ['name' => 'Match'],
        scope: fn (Builder $query) => $query->where('email', 'match@example.com'),
    ));
    $lines = explode("\r\n", substr($response->streamedContent(), 3));

    expect($lines)->toHaveCount(507)
        ->and($lines[1])->toContain('Match 504')
        ->and($lines[505])->toContain('Match 000')
        ->and($response->streamedContent())->not->toContain('Other', 'hidden@example.com');
});

test('csv applies filters when a search matches multiple records', function () {
    CrudTestRecord::query()->create(['name' => 'Ada', 'email' => 'match@example.com']);
    CrudTestRecord::query()->create(['name' => 'Grace', 'email' => 'match@example.com']);

    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download(
        csvTestDefinition(), search: 'match', filters: ['name' => 'Ada'],
    ));

    expect($response->streamedContent())->toContain('Ada')->not->toContain('Grace');
});

test('empty csv exports still contain their headings', function () {
    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download(csvTestDefinition()));

    $response->assertStreamedContent("\xEF\xBB\xBFCorreo;Nombre;Notas;Mayúsculas\r\n");
});

test('export is opt in even when permission is granted', function () {
    app(CrudExportManager::class)->download(new CrudTestRecordDefinition);
})->throws(AuthorizationException::class);

test('an exportable definition still requires export permission', function () {
    Gate::define('export', fn () => false);

    expect(app(CrudSchemaManager::class)->for(csvTestDefinition(), 'records')['operations']['export'])->toBeFalse();
    app(CrudExportManager::class)->download(csvTestDefinition());
})->throws(AuthorizationException::class);

test('an export operation may be explicitly disabled', function () {
    $definition = new class extends CrudTestRecordDefinition implements HasCrudExport, HasCrudOperations
    {
        public function exportColumns(): array
        {
            return [CrudExportColumn::make('name')];
        }

        public function disabledOperations(): array
        {
            return [CrudOperation::Export];
        }
    };

    expect(app(CrudSchemaManager::class)->for($definition, 'records')['operations']['export'])->toBeFalse();
    app(CrudExportManager::class)->download($definition);
})->throws(AuthorizationException::class);

test('csv rejects invalid sorting before starting the download', function () {
    app(CrudExportManager::class)->download(csvTestDefinition(), sort: 'internal_notes');
})->throws(InvalidCrudSortColumn::class);

test('csv requires explicit nonempty export columns', function () {
    $definition = new class extends CrudTestRecordDefinition implements HasCrudExport
    {
        public function exportColumns(): array
        {
            return [];
        }
    };

    app(CrudExportManager::class)->download($definition);
})->throws(InvalidArgumentException::class, 'CSV exports require at least one column.');

test('csv preserves definition query restrictions without running result mapping hooks', function () {
    CrudTestRecord::query()->create(['name' => 'Visible', 'email' => 'visible@example.com']);
    CrudTestRecord::query()->create(['name' => 'Restricted', 'email' => 'restricted@example.com']);
    $definition = new class extends CrudTestRecordDefinition implements HasCrudExport, HasCrudPaginationHooks
    {
        use HandlesCrudPaginationHooks;

        public function exportColumns(): array
        {
            return [CrudExportColumn::make('name')];
        }

        public function beforePaginate(Builder $query): void
        {
            $query->where('name', 'Visible');
        }

        public function afterPaginate(LengthAwarePaginator|Paginator $paginator): void
        {
            throw new RuntimeException('Pagination mapping must not run for exports.');
        }
    };

    $response = TestResponse::fromBaseResponse(app(CrudExportManager::class)->download($definition));

    $response->assertStreamedContent("\xEF\xBB\xBFName\r\nVisible\r\n");
});
