<?php

namespace Modules\Crud\Concerns;

use Modules\Crud\CrudFormMode;
use Modules\Crud\CrudLayoutWidth;

trait HasDefaultCrudPresentation
{
    public function formMode(): CrudFormMode
    {
        return CrudFormMode::from((string) config('crud.default_form_mode', CrudFormMode::Page->value));
    }

    public function pageWidth(): CrudLayoutWidth
    {
        return CrudLayoutWidth::from((string) config('crud.default_page_width', CrudLayoutWidth::Standard->value));
    }

    public function formWidth(): CrudLayoutWidth
    {
        return CrudLayoutWidth::from((string) config('crud.default_form_width', CrudLayoutWidth::Standard->value));
    }

    public function defaultPageSize(): int
    {
        return (int) config('crud.default_page_size', 10);
    }
}
