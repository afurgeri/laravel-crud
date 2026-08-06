<?php

namespace Modules\Crud\Contracts;

interface HasCrudSearchLayout
{
    /**
     * @return array<string, int>
     */
    public function searchSpan(): array;
}
