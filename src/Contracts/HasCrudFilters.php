<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudFilter;

interface HasCrudFilters
{
    /**
     * @return list<CrudFilter>
     */
    public function filters(): array;
}
