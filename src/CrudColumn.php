<?php

namespace Modules\Crud;

use InvalidArgumentException;

final class CrudColumn
{
    private bool $visible = true;

    private bool $sortable = false;

    private bool $searchable = false;

    private bool $computed = false;

    private ?string $label = null;

    private ?string $width = null;

    private ?string $minWidth = null;

    private ?string $maxWidth = null;

    private bool $fixedWidth = false;

    private ?string $temporalType = null;

    private bool $money = false;

    private ?string $currency = null;

    private ?string $currencyColumn = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $labels = null;

    /**
     * @var array<string, string>
     */
    private array $badges = [];

    private bool $isBadge = false;

    private const BADGE_VARIANTS = ['neutral', 'info', 'success', 'warning', 'danger'];

    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
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

    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function minWidth(string $width): self
    {
        $this->minWidth = $width;

        return $this;
    }

    public function maxWidth(string $width): self
    {
        $this->maxWidth = $width;

        return $this;
    }

    public function fixedWidth(string $width): self
    {
        $this->width = $width;
        $this->minWidth = $width;
        $this->maxWidth = $width;
        $this->fixedWidth = true;

        return $this;
    }

    public function widthValue(): ?string
    {
        return $this->width;
    }

    public function minWidthValue(): ?string
    {
        return $this->minWidth;
    }

    public function maxWidthValue(): ?string
    {
        return $this->maxWidth;
    }

    public function hasFixedWidth(): bool
    {
        return $this->fixedWidth;
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

    public function date(): self
    {
        return $this->temporal('date');
    }

    public function time(): self
    {
        return $this->temporal('time');
    }

    public function datetime(): self
    {
        return $this->temporal('datetime');
    }

    public function temporalType(): ?string
    {
        return $this->temporalType;
    }

    /**
     * Display the value as an amount, using a fixed currency or the currency stored in another column of the record.
     */
    public function money(?string $currency = null, ?string $currencyColumn = null): self
    {
        $this->money = true;
        $this->currency = $currency;
        $this->currencyColumn = $currencyColumn;

        return $this;
    }

    public function isMoney(): bool
    {
        return $this->money;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function currencyColumn(): ?string
    {
        return $this->currencyColumn;
    }

    /**
     * Display a label instead of the raw value. When omitted, the options of a select field with the same name are used.
     *
     * @param  array<int|string, string>  $labels
     */
    public function labels(array $labels): self
    {
        $this->labels = array_combine(array_map(strval(...), array_keys($labels)), array_values($labels));

        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function labelsMap(): ?array
    {
        return $this->labels;
    }

    /**
     * Render the value as a badge. Variants: neutral, info, success, warning or danger.
     *
     * @param  array<int|string, 'neutral'|'info'|'success'|'warning'|'danger'>  $variants
     */
    public function badge(array $variants = []): self
    {
        foreach ($variants as $variant) {
            if (! in_array($variant, self::BADGE_VARIANTS, true)) {
                throw new InvalidArgumentException("Unsupported CRUD badge variant [{$variant}].");
            }
        }

        $this->badges = array_combine(array_map(strval(...), array_keys($variants)), array_values($variants));
        $this->isBadge = true;

        return $this;
    }

    public function isBadge(): bool
    {
        return $this->isBadge;
    }

    /**
     * @return array<string, string>
     */
    public function badgeVariants(): array
    {
        return $this->badges;
    }

    private function temporal(string $type): self
    {
        $this->temporalType = $type;

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
