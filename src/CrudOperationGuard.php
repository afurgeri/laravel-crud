<?php

namespace Modules\Crud;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Crud\Contracts\HasCrudOperations;

final class CrudOperationGuard
{
    public static function isEnabled(CrudDefinition $definition, CrudOperation $operation): bool
    {
        if (! $definition instanceof HasCrudOperations) {
            return true;
        }

        return ! in_array($operation, $definition->disabledOperations(), true);
    }

    public static function ensureEnabled(CrudDefinition $definition, CrudOperation $operation): void
    {
        if (! self::isEnabled($definition, $operation)) {
            throw new AuthorizationException("The [{$operation->value}] CRUD operation is disabled.");
        }
    }
}
