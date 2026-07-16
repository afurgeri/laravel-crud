<?php

namespace Modules\Crud\Exceptions;

use RuntimeException;

class CrudDeleteNotAllowed extends RuntimeException
{
    public static function forModel(object $model): self
    {
        return new self(sprintf('The [%s] model cannot be deleted through this CRUD definition.', $model::class));
    }
}
