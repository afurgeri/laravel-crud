<?php

namespace Modules\Crud\Contracts;

interface HasDefaultCrudPageSize
{
    public function defaultPageSize(): int;
}
