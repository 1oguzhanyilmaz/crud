<?php

namespace Oy;

use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->commands(
            'Oy\Commands\CrudCommand',
            'Oy\Commands\CrudControllerCommand',
            'Oy\Commands\CrudModelCommand',
            'Oy\Commands\CrudMigrationCommand',
            'Oy\Commands\CrudViewCommand'
        );
    }

    public function boot()
    {
        // boot method...
    }
}
