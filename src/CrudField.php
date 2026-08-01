<?php

namespace Modules\Crud;

use InvalidArgumentException;

final class CrudField
{
    /**
     * @param  list<string>  $rules
     */
    private function __construct(
        private readonly string $name,
        private array $rules = [],
        private ?string $uniqueColumn = null,
        private bool $visibleOnUpdate = true,
        private string $type = 'text',
        private bool $confirmed = false,
        private ?string $label = null,
        private bool $uniqueItems = false,
        private bool $visible = true,
    ) {}

    private mixed $defaultValue = null;

    private bool $hasDefaultValue = false;

    /**
     * @var array<string, int>
     */
    private array $spans = ['base' => 12];

    /**
     * @param  list<string>  $rules
     */
    public static function make(string $name, array $rules = []): self
    {
        return new self($name, $rules);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function labelKey(): ?string
    {
        return $this->label;
    }

    public function default(mixed $value): self
    {
        $this->defaultValue = $value;
        $this->hasDefaultValue = true;

        return $this;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefaultValue;
    }

    public function defaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * @param  list<string>  $rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function validationRules(): array
    {
        if (! $this->isArray()) {
            return $this->rules;
        }

        return in_array('array', $this->rules, true)
            ? $this->rules
            : [...$this->rules, 'array'];
    }

    public function unique(?string $column = null): self
    {
        $this->uniqueColumn = $column ?? $this->name;

        return $this;
    }

    public function isUnique(): bool
    {
        return $this->uniqueColumn !== null;
    }

    public function uniqueColumn(): ?string
    {
        return $this->uniqueColumn;
    }

    public function createOnly(): self
    {
        $this->visibleOnUpdate = false;

        return $this;
    }

    public function isVisibleOnUpdate(): bool
    {
        return $this->visibleOnUpdate;
    }

    public function visible(bool $visible = true): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function hidden(): self
    {
        return $this->visible(false);
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function span(int $columns, ?string $breakpoint = null): self
    {
        if ($columns < 1 || $columns > 12) {
            throw new InvalidArgumentException('Field spans must be between 1 and 12 columns.');
        }

        $breakpoint ??= 'base';

        if (! in_array($breakpoint, ['base', 'sm', 'md', 'lg', 'xl', '2xl'], true)) {
            throw new InvalidArgumentException("Unsupported field span breakpoint [{$breakpoint}].");
        }

        $this->spans[$breakpoint] = $columns;

        return $this;
    }

    /**
     * @return array<string, int>
     */
    public function spans(): array
    {
        return $this->spans;
    }

    public function email(): self
    {
        $this->type = 'email';

        return $this;
    }

    public function password(): self
    {
        $this->type = 'password';

        return $this;
    }

    public function checkbox(): self
    {
        $this->type = 'checkbox';

        return $this;
    }

    public function number(): self
    {
        $this->type = 'number';

        return $this;
    }

    public function date(): self
    {
        $this->type = 'date';

        return $this;
    }

    public function textarea(): self
    {
        $this->type = 'textarea';

        return $this;
    }

    public function array(bool $unique = false): self
    {
        $this->type = 'array';
        $this->uniqueItems = $unique;

        return $this;
    }

    public function isArray(): bool
    {
        return $this->type === 'array';
    }

    public function uniqueItems(): self
    {
        $this->uniqueItems = true;

        return $this;
    }

    public function hasUniqueItems(): bool
    {
        return $this->uniqueItems;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function confirmed(): self
    {
        $this->rules[] = 'confirmed';
        $this->confirmed = true;

        return $this;
    }

    public function requiresConfirmation(): bool
    {
        return $this->confirmed;
    }
}
