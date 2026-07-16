<?php

namespace Modules\Crud;

use Modules\Crud\Contracts\HasDefaultCrudSort;

final class CrudSortValues
{
    /**
     * Resolve the effective sort column/direction for a definition, falling back to the
     * definition's default sort only when no explicit sort was requested.
     *
     * @return array{column: ?string, direction: 'asc'|'desc'}
     */
    public function for(CrudDefinition $definition, ?string $sort, string $direction): array
    {
        if ($sort === null && $definition instanceof HasDefaultCrudSort) {
            return [
                'column' => $definition->defaultSortColumn(),
                'direction' => $this->normalizeDirection($definition->defaultSortDirection()),
            ];
        }

        return ['column' => $sort, 'direction' => $this->normalizeDirection($direction)];
    }

    /**
     * @return 'asc'|'desc'
     */
    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
