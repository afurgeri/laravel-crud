<?php

namespace Modules\Crud\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Crud\Contracts\HasCrudDefinition;
use Modules\Crud\CrudExportManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CrudExportController extends Controller
{
    public function __invoke(Request $request, string $model, CrudExportManager $exports): StreamedResponse
    {
        abort_unless(is_a($model, HasCrudDefinition::class, true), 404);

        return $exports->download(
            definition: app($model::crudDefinition()),
            sort: $request->string('sort')->toString() ?: null,
            direction: $request->string('direction', 'asc')->toString(),
            search: $request->string('search')->toString() ?: null,
            filters: $request->array('filters'),
        );
    }
}
