<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudFormMode;

interface HasCrudFormMode
{
    public function formMode(): CrudFormMode;
}
