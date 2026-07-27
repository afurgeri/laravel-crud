<?php

namespace Modules\Crud\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HandlesCrudMutationHooks
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function beforeCreate(Model $model, array $data): void {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function afterCreate(Model $model, array $data): void {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function beforeUpdate(Model $model, array $data): void {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function afterUpdate(Model $model, array $data): void {}

    public function beforeDelete(Model $model): void {}

    public function afterDelete(Model $model): void {}
}
