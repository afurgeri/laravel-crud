<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudFormMode;
use Modules\Crud\CrudLayoutWidth;

interface HasCrudPresentation
{
    public function formMode(): CrudFormMode;

    public function pageWidth(): CrudLayoutWidth;

    public function formWidth(): CrudLayoutWidth;
}
