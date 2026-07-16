<?php

namespace Modules\Crud\Concerns;

use Modules\Crud\CrudDefinition;

/** @phpstan-ignore trait.unused */
trait HasCrudDefinition
{
    /**
     * @return class-string<CrudDefinition>
     */
    abstract public static function crudDefinition(): string;

    public static function makeCrudDefinition(): CrudDefinition
    {
        return app(static::crudDefinition());
    }
}
