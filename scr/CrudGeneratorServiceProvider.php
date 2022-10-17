<?php

namespace Oks\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Oks\CrudGenerator\Console\CrudGenerator;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }
        $this->publishes([
            __DIR__.'/resources/stubs/Controller.stub' => resource_path("stubs/Controller.stub"),
            __DIR__.'/resources/stubs/Model.stub' => resource_path("stubs/Model.stub"),
        ]);
    }
    public function register()
    {
    }
}
