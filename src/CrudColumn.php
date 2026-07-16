<?php

namespace Modules\Crud;

final class CrudColumn
{
    private bool $visible = true;

    private bool $sortable = false;

    private bool $searchable = false;

    private bool $computed = false;

    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function name(): string
    {
        return $this->name;
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

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function computed(bool $computed = true): self
    {
        $this->computed = $computed;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isComputed(): bool
    {
        return $this->computed;
    }
}
