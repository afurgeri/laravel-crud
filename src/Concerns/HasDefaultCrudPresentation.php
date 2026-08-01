<?php

namespace Modules\Crud\Concerns;

use Modules\Crud\CrudFormMode;
use Modules\Crud\CrudLayoutWidth;

trait HasDefaultCrudPresentation
{
    public function formMode(): CrudFormMode
    {
        return CrudFormMode::Page;
    }

    public function pageWidth(): CrudLayoutWidth
    {
        return CrudLayoutWidth::Standard;
    }

    public function formWidth(): CrudLayoutWidth
    {
        return CrudLayoutWidth::Standard;
    }
}
