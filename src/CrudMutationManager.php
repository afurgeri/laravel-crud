<?php

namespace Modules\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\GuardsCrudDeletes;
use Modules\Crud\Contracts\HasCrudMutationHooks;
use Modules\Crud\Exceptions\CrudDeleteNotAllowed;

class CrudMutationManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(CrudDefinition $definition, array $data): Model
    {
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Create);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        $model = $definition->model();

        /** @var Model $instance */
        $instance = new $model;
        $instance->fill($this->validatedData($definition, $data, null));

        if ($definition instanceof HasCrudMutationHooks) {
            $definition->beforeCreate($instance, $data);
        }

        $instance->save();

        if ($definition instanceof HasCrudMutationHooks) {
            $definition->afterCreate($instance, $data);
        }

        return $instance;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, CrudDefinition $definition, array $data): Model
    {
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Update);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($model);
        }

        $model->fill($this->validatedData($definition, $data, $model));

        if ($definition instanceof HasCrudMutationHooks) {
            $definition->beforeUpdate($model, $data);
        }

        $model->save();
        $model = $model->refresh();

        if ($definition instanceof HasCrudMutationHooks) {
            $definition->afterUpdate($model, $data);
        }

        return $model;
    }

    public function delete(Model $model, CrudDefinition $definition): bool
    {
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Delete);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeDelete($model);
        }

        if ($definition instanceof GuardsCrudDeletes && ! $definition->canDelete($model)) {
            throw CrudDeleteNotAllowed::forModel($model);
        }

        if ($definition instanceof HasCrudMutationHooks) {
            $definition->beforeDelete($model);
        }

        $deleted = (bool) $model->delete();

        if ($deleted && $definition instanceof HasCrudMutationHooks) {
            $definition->afterDelete($model);
        }

        return $deleted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatedData(CrudDefinition $definition, array $data, ?Model $model): array
    {
        $validated = Validator::make($data, $this->validationRules($definition, $model))->validate();
        $modelClass = $definition->model();

        /** @var Model $caster */
        $caster = new $modelClass;

        foreach (array_keys($validated) as $attribute) {
            $castType = $caster->getCasts()[$attribute] ?? null;

            if ($castType === null) {
                continue;
            }

            $castType = strtolower((string) str($castType)->before(':'));

            $validated[$attribute] = match ($castType) {
                'boolean' => $this->booleanValue($validated[$attribute]),
                default => $this->castValue($caster, $attribute, $validated[$attribute]),
            };
        }

        return $validated;
    }

    private function castValue(Model $caster, string $attribute, mixed $value): mixed
    {
        $caster->setAttribute($attribute, $value);

        return $caster->getAttribute($attribute);
    }

    private function booleanValue(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? (bool) $value;
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

            if ($field->isArray()) {
                $rules[$field->name().'.*'] = ['string'];

                if ($field->hasUniqueItems()) {
                    $rules[$field->name().'.*'][] = 'distinct:strict';
                }
            }

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
