<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudOperation;

interface HasCrudOperations
{
    /**
     * @return list<CrudOperation>
     */
    public function disabledOperations(): array;
}
