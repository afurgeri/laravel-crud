<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudFormMode;

interface HasCrudFormMode
{
    /**
     * @deprecated Use HasCrudPresentation instead.
     */
    public function formMode(): CrudFormMode;
}
