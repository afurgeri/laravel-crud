<?php

namespace Modules\Crud\Contracts;

interface EagerLoadsCrudRelations
{
    /**
     * @return list<string>
     */
    public function eagerLoads(): array;
}
