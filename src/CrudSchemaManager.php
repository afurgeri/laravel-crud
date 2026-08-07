<?php

namespace Modules\Crud;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudFormMode;
use Modules\Crud\Contracts\HasCrudOperations;
use Modules\Crud\Contracts\HasCrudPresentation;
use Modules\Crud\Contracts\HasCrudSearchLayout;

class CrudSchemaManager
{
    public function __construct(
        private readonly CrudFilterValues $filterValues,
        private readonly CrudSortValues $sortValues,
    ) {}

    /**
     * @param  array<string, mixed>  $filterValues
     * @return array{
     *     resource: string,
     *     form_mode: 'dialog'|'page',
     *     page_width: 'standard'|'wide'|'full',
     *     form_width: 'standard'|'wide'|'full',
     *     operations: array{show: bool, create: bool, update: bool, delete: bool},
     *     title: string,
     *     description: string|null,
     *     empty_label: string|null,
     *     columns: list<array{name: string, label: string, sortable: bool, width?: string, min_width?: string, max_width?: string, fixed?: bool}>,
     *     fields: list<array{name: string, label: string, type: string, confirmed: bool, required: bool, rules: list<string>, unique_items?: bool, visible: bool, visible_on_update: bool, span: array<string, int>, defaultValue?: mixed, options?: list<array{value: string, label: string}>}>,
     *     sort: array{column: ?string, direction: 'asc'|'desc'},
     *     search: array{enabled: bool, value: ?string, span: array<string, int>},
     *     filters: list<array{name: string, label: string, type: string, operator: string, relation: bool, clearable: bool, range: ?string, value: mixed, span: array<string, int>, options?: list<array{value: string, label: string}>, remote?: array{url: string, min_chars: int, debounce: int}, max_date?: ?string}>
     * }
     */
    public function for(CrudDefinition $definition, string $resource, ?string $sort = null, string $direction = 'asc', ?string $search = null, array $filterValues = []): array
    {
        $filters = $definition instanceof HasCrudFilters ? $definition->filters() : [];
        $effectiveFilterValues = $definition instanceof HasCrudFilters
            ? $this->filterValues->for($filters, $filterValues)
            : [];

        $operations = array_fill_keys(array_column(CrudOperation::cases(), 'value'), true);

        if ($definition instanceof HasCrudOperations) {
            foreach ($definition->disabledOperations() as $operation) {
                $operations[$operation->value] = false;
            }
        }

        return [
            'resource' => $resource,
            'form_mode' => $this->formMode($definition)->value,
            'page_width' => $this->pageWidth($definition)->value,
            'form_width' => $this->formWidth($definition)->value,
            'operations' => $operations,
            'title' => $definition->title(),
            'description' => $definition->description(),
            'empty_label' => $definition->emptyLabel(),
            'columns' => array_map(
                fn (CrudColumn $column): array => $this->columnSchema($column),
                array_values(array_filter(
                    $definition->columns(),
                    fn (CrudColumn $column): bool => $column->isVisible(),
                )),
            ),
            'fields' => array_map(
                fn (CrudField $field): array => $this->fieldSchema($field, $resource),
                $definition->fields(),
            ),
            'sort' => $this->sortValues->for($definition, $sort, $direction),
            'search' => [
                'enabled' => $this->hasSearchableColumns($definition),
                'value' => $search,
                'span' => $this->searchSpan($definition),
            ],
            'filters' => $definition instanceof HasCrudFilters
                ? array_map(
                    fn (CrudFilter $filter): array => $this->filterSchema($filter, $effectiveFilterValues, $resource),
                    $filters,
                )
                : [],
        ];
    }

    private function formMode(CrudDefinition $definition): CrudFormMode
    {
        if ($definition instanceof HasCrudPresentation) {
            return $definition->formMode();
        }

        return $definition instanceof HasCrudFormMode
            ? $definition->formMode()
            : CrudFormMode::Page;
    }

    private function pageWidth(CrudDefinition $definition): CrudLayoutWidth
    {
        return $definition instanceof HasCrudPresentation
            ? $definition->pageWidth()
            : CrudLayoutWidth::Standard;
    }

    private function formWidth(CrudDefinition $definition): CrudLayoutWidth
    {
        return $definition instanceof HasCrudPresentation
            ? $definition->formWidth()
            : CrudLayoutWidth::Standard;
    }

    /**
     * @return array{name: string, label: string, sortable: bool, width?: string, min_width?: string, max_width?: string, fixed?: bool}
     */
    private function columnSchema(CrudColumn $column): array
    {
        $schema = [
            'name' => $column->name(),
            'label' => $this->label($column->labelKey(), $column->name()),
            'sortable' => $column->isSortable(),
        ];

        if ($column->widthValue() !== null) {
            $schema['width'] = $column->widthValue();
        }

        if ($column->minWidthValue() !== null) {
            $schema['min_width'] = $column->minWidthValue();
        }

        if ($column->maxWidthValue() !== null) {
            $schema['max_width'] = $column->maxWidthValue();
        }

        if ($column->hasFixedWidth()) {
            $schema['fixed'] = true;
        }

        return $schema;
    }

    /**
     * @return array{name: string, label: string, type: string, confirmed: bool, required: bool, rules: list<string>, clearable: bool, unique_items?: bool, visible: bool, visible_on_update: bool, span: array<string, int>, defaultValue?: mixed, step?: string, options?: list<array{value: string, label: string}>, remote?: array{url: string, min_chars: int, debounce: int, source: 'field'}}
     */
    private function fieldSchema(CrudField $field, string $resource): array
    {
        $rules = $field->validationRules();

        $schema = [
            'name' => $field->name(),
            'label' => $this->label($field->labelKey(), $field->name()),
            'type' => $field->type(),
            'confirmed' => $field->requiresConfirmation(),
            'required' => in_array('required', $rules, true),
            'rules' => $rules,
            'clearable' => $field->isClearable(),
            'visible' => $field->isVisible(),
            'visible_on_update' => $field->isVisibleOnUpdate(),
            'span' => $field->spans(),
        ];

        if ($field->isArray()) {
            $schema['unique_items'] = $field->hasUniqueItems();
        }

        if ($field->step() !== null) {
            $schema['step'] = $field->step();
        }

        if (in_array($field->type(), ['select', 'combobox'], true)) {
            $schema['options'] = array_map(
                fn (array $option): array => [
                    'value' => $this->optionValue($option['value']),
                    'label' => $option['label'],
                ],
                $this->fieldOptions($field->options()),
            );
        }

        if ($field->isRemote()) {
            $routeName = "{$resource}.options";
            $generatedUrl = Route::has($routeName)
                ? route($routeName, ['filter' => $field->name()])
                : url("{$resource}/options/{$field->name()}");

            $schema['remote'] = [
                ...$field->remoteConfig($generatedUrl),
                'source' => 'field',
            ];
        }

        if ($field->hasDefault()) {
            $schema['defaultValue'] = $field->defaultValue();
        }

        return $schema;
    }

    private function optionValue(bool|float|int|string|null $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            $value === null => '',
            default => (string) $value,
        };
    }

    /**
     * @param  list<array{value: bool|float|int|string|null, label: string}>|array<int|string, string>  $options
     * @return list<array{value: bool|float|int|string|null, label: string}>
     */
    private function fieldOptions(array $options): array
    {
        if (array_is_list($options) && ($options === [] || is_array($options[0]))) {
            /** @var list<array{value: bool|float|int|string|null, label: string}> $options */
            return $options;
        }

        $normalized = [];

        foreach ($options as $value => $label) {
            if (! is_string($label)) {
                throw new InvalidArgumentException('Field option labels must be strings.');
            }

            $normalized[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filterValues  Current values of every filter, forwarded so cascading select filters can narrow their options.
     * @return array{name: string, label: string, type: string, operator: string, relation: bool, clearable: bool, range: ?string, value: mixed, span: array<string, int>, options?: list<array{value: string, label: string}>, remote?: array{url: string, min_chars: int, debounce: int}, max_date?: ?string}
     */
    private function filterSchema(CrudFilter $filter, array $filterValues, string $resource): array
    {
        $schema = [
            'name' => $filter->name(),
            'label' => $this->label($filter->labelKey(), $filter->name()),
            'type' => $filter->type(),
            'operator' => $filter->comparisonOperator(),
            'relation' => $filter->isRelation(),
            'clearable' => $filter->isClearable(),
            'range' => $filter->rangeGroup(),
            'value' => array_key_exists($filter->name(), $filterValues)
                ? $filterValues[$filter->name()]
                : $filter->resolvedDefault(),
            'span' => $filter->spans(),
        ];

        if (in_array($filter->type(), ['select', 'combobox'], true)) {
            $options = $filter->resolvedOptions($filterValues);

            $schema['options'] = array_map(
                fn (int|string $optionValue, string $optionLabel): array => [
                    'value' => (string) $optionValue,
                    'label' => $optionLabel,
                ],
                array_keys($options),
                array_values($options),
            );
        }

        if ($filter->isRemote()) {
            $routeName = "{$resource}.options";
            $generatedUrl = Route::has($routeName)
                ? route($routeName, ['filter' => $filter->name()])
                : url("{$resource}/options/{$filter->name()}");

            $schema['remote'] = $filter->remoteConfig($generatedUrl);
        }

        if ($filter->type() === 'date') {
            $schema['max_date'] = $filter->resolvedMaxDate();
        }

        return $schema;
    }

    private function hasSearchableColumns(CrudDefinition $definition): bool
    {
        return collect($definition->columns())->contains(fn (CrudColumn $column): bool => $column->isSearchable());
    }

    /**
     * @return array<string, int>
     */
    private function searchSpan(CrudDefinition $definition): array
    {
        return $definition instanceof HasCrudSearchLayout
            ? $this->normalizeSpan($definition->searchSpan(), 'Search')
            : ['base' => 12];
    }

    /**
     * @param  array<string, int>  $spans
     * @return array<string, int>
     */
    private function normalizeSpan(array $spans, string $subject): array
    {
        $normalized = ['base' => 12];

        foreach ($spans as $breakpoint => $columns) {
            if ($columns < 1 || $columns > 12) {
                throw new InvalidArgumentException("{$subject} spans must be between 1 and 12 columns.");
            }

            if (! in_array($breakpoint, ['base', 'sm', 'md', 'lg', 'xl', '2xl'], true)) {
                throw new InvalidArgumentException("Unsupported {$subject} span breakpoint [{$breakpoint}].");
            }

            $normalized[$breakpoint] = $columns;
        }

        return $normalized;
    }

    private function label(?string $labelKey, string $name): string
    {
        $key = $labelKey ?? Str::of($name)->replace('_', ' ')->headline()->toString();

        return app()->bound('translator') ? __($key) : $key;
    }
}
