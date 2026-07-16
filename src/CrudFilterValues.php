<?php

namespace Modules\Crud;

final class CrudFilterValues
{
    /**
     * @param  list<CrudFilter>  $filters
     * @param  array<string, mixed>  $submittedValues
     * @return array<string, mixed>
     */
    public function for(array $filters, array $submittedValues): array
    {
        $values = [];

        foreach ($filters as $filter) {
            $values[$filter->name()] = $this->effectiveValue($filter, $submittedValues);
        }

        foreach ($filters as $filter) {
            if ($filter->type() !== 'select') {
                continue;
            }

            $value = $values[$filter->name()] ?? null;

            if ($this->isEmpty($value)) {
                continue;
            }

            $options = $filter->resolvedOptions($values);

            if ($options !== [] && ! $this->optionExists($options, $value)) {
                $values[$filter->name()] = null;
            }
        }

        return $values;
    }

    /**
     * Resolves the value that should be used for a filter: the submitted value if
     * present, otherwise the filter default. Empty submitted values intentionally
     * fall back to defaults, matching the existing Clear filters behavior.
     *
     * @param  array<string, mixed>  $submittedValues
     */
    private function effectiveValue(CrudFilter $filter, array $submittedValues): mixed
    {
        $value = $submittedValues[$filter->name()] ?? null;

        if ($this->isEmpty($value)) {
            return $filter->resolvedDefault();
        }

        return $value;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * @param  array<int|string, string>  $options
     */
    private function optionExists(array $options, mixed $value): bool
    {
        foreach (array_keys($options) as $optionValue) {
            if ((string) $optionValue === (string) $value) {
                return true;
            }
        }

        return false;
    }
}
