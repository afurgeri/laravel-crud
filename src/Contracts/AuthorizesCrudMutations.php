<?php

namespace Modules\Crud\Contracts;

use Illuminate\Database\Eloquent\Model;

interface AuthorizesCrudMutations
{
    public function authorizeCreate(): void;

    public function authorizeUpdate(Model $model): void;

    public function authorizeDelete(Model $model): void;
}
