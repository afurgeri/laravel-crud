<?php

namespace Modules\Crud\Contracts;

interface HasDefaultCrudSort
{
    public function defaultSortColumn(): string;

    public function defaultSortDirection(): string;
}
