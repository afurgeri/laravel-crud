<?php

namespace Modules\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\GuardsCrudDeletes;
use Modules\Crud\Exceptions\CrudDeleteNotAllowed;

class CrudMutationManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(CrudDefinition $definition, array $data): Model
    {
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        $model = $definition->model();

        /** @var Model $instance */
        $instance = new $model;
        $instance->fill($this->validatedData($definition, $data, null));
        $instance->save();

        return $instance;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, CrudDefinition $definition, array $data): Model
    {
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($model);
        }

        $model->fill($this->validatedData($definition, $data, $model));
        $model->save();

        return $model->refresh();
    }

    public function delete(Model $model, CrudDefinition $definition): bool
    {
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeDelete($model);
        }

        if ($definition instanceof GuardsCrudDeletes && ! $definition->canDelete($model)) {
            throw CrudDeleteNotAllowed::forModel($model);
        }

        return (bool) $model->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatedData(CrudDefinition $definition, array $data, ?Model $model): array
    {
        return Validator::make($data, $this->validationRules($definition, $model))->validate();
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function validationRules(CrudDefinition $definition, ?Model $model): array
    {
        $rules = [];
        $modelClass = $definition->model();

        /** @var Model $instance */
        $instance = new $modelClass;

        foreach ($definition->fields() as $field) {
            if ($model !== null && ! $field->isVisibleOnUpdate()) {
                continue;
            }

            $rules[$field->name()] = $field->validationRules();

            if ($field->isUnique()) {
                $table = $instance->getTable();

                if ($instance->getConnectionName() !== null) {
                    $table = $instance->getConnectionName().'.'.$table;
                }

                $rule = Rule::unique($table, $field->uniqueColumn() ?? $field->name());

                if ($model !== null) {
                    $rule->ignore($model);
                }

                $rules[$field->name()][] = $rule;
            }
        }

        return $rules;
    }
}
