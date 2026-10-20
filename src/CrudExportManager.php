<?php

namespace Modules\Crud;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Crud\Contracts\HasCrudExport;
use RuntimeException;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CrudExportManager
{
    public function __construct(private readonly CrudQueryManager $queries) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  Closure(Builder<Model>): mixed|null  $scope
     */
    public function download(
        CrudDefinition $definition,
        ?string $sort = null,
        string $direction = 'asc',
        ?string $search = null,
        array $filters = [],
        ?Closure $scope = null,
    ): StreamedResponse {
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Export);
        Gate::authorize('export', $definition->model());

        if (! $definition instanceof HasCrudExport) {
            throw new InvalidArgumentException('This CRUD definition does not support exports.');
        }

        $columns = $definition->exportColumns();

        if ($columns === []) {
            throw new InvalidArgumentException('CSV exports require at least one column.');
        }

        $query = $this->queries->for($definition, $sort, $direction, $search, $filters, $scope);
        $query->orderBy($query->getModel()->getQualifiedKeyName());
        $filename = Str::kebab(class_basename($definition->model())).'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($columns, $query): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            try {
                fwrite($stream, "\xEF\xBB\xBF");
                $this->writeRow($stream, array_map(fn (CrudExportColumn $column): string => $column->heading(), $columns));

                foreach ($query->lazy(500) as $record) {
                    $this->writeRow($stream, array_map(fn (CrudExportColumn $column): mixed => $column->value($record), $columns));
                }
            } finally {
                fclose($stream);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  resource  $stream
     * @param  list<mixed>  $values
     */
    private function writeRow($stream, array $values): void
    {
        if (fputcsv($stream, array_map($this->cell(...), $values), ';', '"', '', "\r\n") === false) {
            throw new RuntimeException('Unable to write a CSV row.');
        }
    }

    private function cell(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value) && ! $value instanceof Stringable) {
            throw new InvalidArgumentException('CSV values must be scalar or stringable. Use valueUsing() to export complex values.');
        }

        $value = (string) $value;

        return preg_match('/^[\s\p{Z}\x{FEFF}]*[=+\-@\t\r\n]/u', $value) === 1
            ? "'".$value
            : $value;
    }
}
