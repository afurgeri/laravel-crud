<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\EagerLoadsCrudRelations;

class CrudTestRecordWithProfessionalDefinition extends CrudTestRecordDefinition implements EagerLoadsCrudRelations
{
    public function eagerLoads(): array
    {
        return ['professional'];
    }
}
