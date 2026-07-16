<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudDefinition;

interface HasCrudDefinition
{
    /**
     * @return class-string<CrudDefinition>
     */
    public static function crudDefinition(): string;
}
