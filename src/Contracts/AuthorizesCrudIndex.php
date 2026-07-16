<?php

namespace Modules\Crud\Contracts;

interface AuthorizesCrudIndex
{
    public function authorizeViewAny(): void;
}
