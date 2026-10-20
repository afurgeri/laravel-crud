<?php

namespace Modules\Crud;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CrudExportColumn
{
    private ?string $header = null;

    /** @var Closure(Model): mixed|null */
    private ?Closure $valueResolver = null;

    /** @var Closure(mixed, Model): mixed|null */
    private ?Closure $formatter = null;

    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function header(string $header): self
    {
        $this->header = $header;

        return $this;
    }

    /** @param Closure(Model): mixed $resolver */
    public function valueUsing(Closure $resolver): self
    {
        $this->valueResolver = $resolver;

        return $this;
    }

    /** @param Closure(mixed, Model): mixed $formatter */
    public function formatUsing(Closure $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function heading(): string
    {
        $key = $this->header ?? Str::headline($this->name);

        return app()->bound('translator') ? __($key) : $key;
    }

    public function value(Model $record): mixed
    {
        $value = $this->valueResolver !== null
            ? ($this->valueResolver)($record)
            : data_get($record, $this->name);

        return $this->formatter !== null ? ($this->formatter)($value, $record) : $value;
    }
}
