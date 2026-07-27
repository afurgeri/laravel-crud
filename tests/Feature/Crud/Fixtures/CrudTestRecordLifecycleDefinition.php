<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\HandlesCrudMutationHooks;
use Modules\Crud\Contracts\HasCrudMutationHooks;

class CrudTestRecordLifecycleDefinition extends CrudTestRecordAuthorizedDefinition implements HasCrudMutationHooks
{
    use HandlesCrudMutationHooks;

    /** @var list<string> */
    public static array $events = [];

    /** @var array<string, mixed> */
    public static array $lastData = [];

    public function beforeCreate(Model $model, array $data): void
    {
        self::$events[] = 'beforeCreate';
        self::$lastData = $data;
    }

    public function afterCreate(Model $model, array $data): void
    {
        self::$events[] = 'afterCreate';
        self::$lastData = $data;
    }

    public function beforeUpdate(Model $model, array $data): void
    {
        self::$events[] = 'beforeUpdate';
        self::$lastData = $data;
    }

    public function afterUpdate(Model $model, array $data): void
    {
        self::$events[] = 'afterUpdate';
        self::$lastData = $data;
    }

    public function beforeDelete(Model $model): void
    {
        self::$events[] = 'beforeDelete';
    }

    public function afterDelete(Model $model): void
    {
        self::$events[] = 'afterDelete';
    }
}
