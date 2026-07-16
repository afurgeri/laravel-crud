<?php

namespace Tests\Feature\Crud\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use RuntimeException;

class CrudTestRecordAuthorizedDefinition extends CrudTestRecordDefinition implements AuthorizesCrudMutations
{
    public static bool $authorized = true;

    public static int $createCalls = 0;

    public static int $updateCalls = 0;

    public static int $deleteCalls = 0;

    public function authorizeCreate(): void
    {
        self::$createCalls++;
        $this->guard();
    }

    public function authorizeUpdate(Model $model): void
    {
        self::$updateCalls++;
        $this->guard();
    }

    public function authorizeDelete(Model $model): void
    {
        self::$deleteCalls++;
        $this->guard();
    }

    private function guard(): void
    {
        if (! self::$authorized) {
            throw new RuntimeException('Unauthorized crud mutation.');
        }
    }
}
