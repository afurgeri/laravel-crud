<?php

namespace Modules\Crud;

use Illuminate\Support\Str;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudFormMode;
use Modules\Crud\Contracts\HasCrudOperations;

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
     *     operations: array{show: bool, create: bool, update: bool, delete: bool},
     *     title: string,
     *     description: string|null,
     *     empty_label: string|null,
     *     columns: list<array{name: string, label: string, sortable: bool}>,
     *     fields: list<array{name: string, label: string, type: string, confirmed: bool, required: bool, rules: list<string>, visible_on_update: bool}>,
     *     sort: array{column: ?string, direction: 'asc'|'desc'},
     *     search: array{enabled: bool, value: ?string},
     *     filters: list<array{name: string, label: string, type: string, operator: string, relation: bool, clearable: bool, range: ?string, value: mixed, options?: list<array{value: string, label: string}>, max_date?: ?string}>
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
            'form_mode' => ($definition instanceof HasCrudFormMode
                ? $definition->formMode()
                : CrudFormMode::Page)->value,
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
                fn (CrudField $field): array => $this->fieldSchema($field),
                $definition->fields(),
            ),
            'sort' => $this->sortValues->for($definition, $sort, $direction),
            'search' => [
                'enabled' => $this->hasSearchableColumns($definition),
                'value' => $search,
            ],
            'filters' => $definition instanceof HasCrudFilters
                ? array_map(
                    fn (CrudFilter $filter): array => $this->filterSchema($filter, $effectiveFilterValues),
                    $filters,
                )
                : [],
        ];
    }

    /**
     * @return array{name: string, label: string, sortable: bool}
     */
    private function columnSchema(CrudColumn $column): array
    {
        return [
            'name' => $column->name(),
            'label' => $this->label($column->name()),
            'sortable' => $column->isSortable(),
        ];
    }

    /**
     * @return array{name: string, label: string, type: string, confirmed: bool, required: bool, rules: list<string>, visible_on_update: bool}
     */
    private function fieldSchema(CrudField $field): array
    {
        $rules = $field->validationRules();

        return [
            'name' => $field->name(),
            'label' => $this->label($field->name()),
            'type' => $field->type(),
            'confirmed' => $field->requiresConfirmation(),
            'required' => in_array('required', $rules, true),
            'rules' => $rules,
            'visible_on_update' => $field->isVisibleOnUpdate(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filterValues  Current values of every filter, forwarded so cascading select filters can narrow their options.
     * @return array{name: string, label: string, type: string, operator: string, relation: bool, clearable: bool, range: ?string, value: mixed, options?: list<array{value: string, label: string}>, max_date?: ?string}
     */
    private function filterSchema(CrudFilter $filter, array $filterValues): array
    {
        $schema = [
            'name' => $filter->name(),
            'label' => $this->label($filter->name()),
            'type' => $filter->type(),
            'operator' => $filter->comparisonOperator(),
            'relation' => $filter->isRelation(),
            'clearable' => $filter->isClearable(),
            'range' => $filter->rangeGroup(),
            'value' => array_key_exists($filter->name(), $filterValues)
                ? $filterValues[$filter->name()]
                : $filter->resolvedDefault(),
        ];

        if ($filter->type() === 'select') {
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

        if ($filter->type() === 'date') {
            $schema['max_date'] = $filter->resolvedMaxDate();
        }

        return $schema;
    }

    private function hasSearchableColumns(CrudDefinition $definition): bool
    {
        return collect($definition->columns())->contains(fn (CrudColumn $column): bool => $column->isSearchable());
    }

    private function label(string $name): string
    {
        return Str::of($name)->replace('_', ' ')->headline()->toString();
    }
}
