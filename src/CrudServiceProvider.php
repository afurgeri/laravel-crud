<?php

namespace Modules\Crud;

use Illuminate\Support\ServiceProvider;
use Modules\Crud\Console\Commands\InstallCrudCommand;
use Modules\Crud\Console\Commands\MakeCrudCommand;

class CrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCrudCommand::class,
                MakeCrudCommand::class,
            ]);
        }
    }
}
