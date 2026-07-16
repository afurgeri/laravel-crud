<?php

namespace Modules\Crud;

use Closure;
use ReflectionFunction;

final class CrudFilter
{
    public const OPERATORS = ['=', '!=', '>', '>=', '<', '<='];

    private string $type = 'text';

    private string $operator = '=';

    private ?string $relation = null;

    private string $relationColumn = 'id';

    private ?string $rangeGroup = null;

    /** @var array<int|string, string>|Closure(): array<int|string, string>|Closure(array<string, mixed>): array<int|string, string>|null */
    private array|Closure|null $options = null;

    private string|Closure|null $maxDate = null;

    private string|Closure|null $default = null;

    private bool $clearable = false;

    private function __construct(private readonly string $name, private readonly string $column) {}

    public static function make(string $name, ?string $column = null): self
    {
        return new self($name, $column ?? $name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function text(): self
    {
        $this->type = 'text';

        return $this;
    }

    public function date(): self
    {
        $this->type = 'date';

        return $this;
    }

    public function number(): self
    {
        $this->type = 'number';

        return $this;
    }

    /**
     * @param  array<int|string, string>|Closure(): array<int|string, string>|Closure(array<string, mixed>): array<int|string, string>  $options
     */
    public function select(array|Closure $options): self
    {
        $this->type = 'select';
        $this->options = $options;

        return $this;
    }

    public function operator(string $operator): self
    {
        $this->operator = in_array($operator, self::OPERATORS, true) ? $operator : '=';

        return $this;
    }

    public function relation(string $relation, string $column = 'id'): self
    {
        $this->relation = $relation;
        $this->relationColumn = $column;

        return $this;
    }

    /**
     * Marks this filter as one bound of a logical range (e.g. "created_from" and
     * "created_to" sharing the group "created_at"), so the frontend can render them
     * together and the backend can validate the lower bound isn't after the upper one.
     */
    public function range(string $group): self
    {
        $this->rangeGroup = $group;

        return $this;
    }

    public function maxDate(string|Closure $maxDate): self
    {
        $this->maxDate = $maxDate;

        return $this;
    }

    public function default(string|Closure $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Controls whether the frontend shows an individual "clear this filter" affordance
     * for this filter. Not clearable by default; opt in for filters that have no other
     * easy way to clear (e.g. a select filter, which can't be deselected by typing).
     */
    public function clearable(bool $clearable = true): self
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function comparisonOperator(): string
    {
        return $this->operator;
    }

    public function isRelation(): bool
    {
        return $this->relation !== null;
    }

    public function relationName(): ?string
    {
        return $this->relation;
    }

    public function relationColumn(): string
    {
        return $this->relationColumn;
    }

    public function rangeGroup(): ?string
    {
        return $this->rangeGroup;
    }

    /**
     * @param  array<string, mixed>  $filterValues  Current values of every filter, so cascading filters can narrow their options based on another filter's selection.
     * @return array<int|string, string>
     */
    public function resolvedOptions(array $filterValues = []): array
    {
        if ($this->options instanceof Closure) {
            if ((new ReflectionFunction($this->options))->getNumberOfParameters() === 0) {
                return ($this->options)();
            }

            return ($this->options)($filterValues);
        }

        return $this->options ?? [];
    }

    public function resolvedMaxDate(): ?string
    {
        if ($this->maxDate instanceof Closure) {
            return ($this->maxDate)();
        }

        return $this->maxDate;
    }

    public function resolvedDefault(): ?string
    {
        if ($this->default instanceof Closure) {
            return ($this->default)();
        }

        return $this->default;
    }

    public function isClearable(): bool
    {
        return $this->clearable;
    }
}
