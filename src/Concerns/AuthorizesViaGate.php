<?php

namespace Modules\Crud\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait AuthorizesViaGate
{
    abstract public function model(): string;

    public function authorizeViewAny(): void
    {
        Gate::authorize('viewAny', $this->model());
    }

    public function authorizeCreate(): void
    {
        Gate::authorize('create', $this->model());
    }

    public function authorizeUpdate(Model $model): void
    {
        Gate::authorize('update', $model);
    }

    public function authorizeDelete(Model $model): void
    {
        Gate::authorize('delete', $model);
    }
}
