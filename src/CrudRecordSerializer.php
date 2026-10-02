<?php

namespace Modules\Crud;

use Illuminate\Database\Eloquent\Model;

final class CrudRecordSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Model $model, CrudDefinition $definition): array
    {
        $record = $model->toArray();

        foreach ($definition->fields() as $field) {
            if ($field->type() !== 'datetime' || ! array_key_exists($field->name(), $record)) {
                continue;
            }

            $record[$field->name()] = CrudTemporal::serializeDateTime(
                $model->getAttribute($field->name()),
            );
        }

        return $record;
    }
}
