<?php

namespace Modules\Crud;

use Illuminate\Database\Eloquent\Model;

interface CrudDefinition
{
    /**
     * @return class-string<Model>
     */
    public function model(): string;

    public function title(): string;

    public function description(): ?string;

    public function emptyLabel(): ?string;

    /**
     * @return list<CrudColumn>
     */
    public function columns(): array;

    /**
     * @return list<CrudField>
     */
    public function fields(): array;
}
