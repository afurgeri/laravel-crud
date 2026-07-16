<?php

namespace Tests\Feature\Crud\Fixtures;

use Modules\Crud\Contracts\EagerLoadsCrudRelations;

class CrudTestRecordWithNotesDefinition extends CrudTestRecordDefinition implements EagerLoadsCrudRelations
{
    public function eagerLoads(): array
    {
        return ['notes:id,crud_test_record_id,body'];
    }
}
