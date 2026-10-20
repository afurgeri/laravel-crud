<?php

namespace Modules\Crud\Contracts;

use Modules\Crud\CrudExportColumn;

interface HasCrudExport
{
    /**
     * @return list<CrudExportColumn>
     */
    public function exportColumns(): array;
}
