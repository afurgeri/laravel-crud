<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\AuthorizesCrudIndex;
use RuntimeException;

class CrudTestRecordIndexAuthorizedDefinition extends CrudTestRecordDefinition implements AuthorizesCrudIndex
{
    public static bool $authorized = true;

    public static int $viewAnyCalls = 0;

    public function authorizeViewAny(): void
    {
        self::$viewAnyCalls++;

        if (! self::$authorized) {
            throw new RuntimeException('Unauthorized crud index view.');
        }
    }
}
