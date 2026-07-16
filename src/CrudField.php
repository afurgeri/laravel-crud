<?php

namespace Modules\Crud;

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
    ) {}

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
        return $this->rules;
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
