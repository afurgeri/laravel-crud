<?php

namespace Modules\Crud\Contracts;

use Illuminate\Database\Eloquent\Model;

interface GuardsCrudDeletes
{
    public function canDelete(Model $model): bool;
}
