<?php

namespace Modules\Crud;

use Illuminate\Support\Facades\Route;
use Modules\Crud\Http\Controllers\CrudExportController;
use Modules\Crud\Http\Controllers\CrudOptionsController;

final class CrudRouteRegistrar
{
    public function register(string $resource, string $controller, string $model): void
    {
        Route::get("{$resource}/export", CrudExportController::class)
            ->name("{$resource}.export")
            ->defaults('model', $model);

        Route::get("{$resource}/options/{filter}", CrudOptionsController::class)
            ->name("{$resource}.options")
            ->defaults('model', $model);

        Route::resource($resource, $controller)->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update',
            'destroy',
        ]);
    }
}
