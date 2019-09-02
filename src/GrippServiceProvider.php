<?php

namespace Sqits\Gripp;

use Illuminate\Support\ServiceProvider;

class GrippServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/gripp.php', 'gripp'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file.
        $this->publishes([
            __DIR__.'/../config/gripp.php' => config_path('gripp.php'),
        ], 'config');

        // Routes
        //$this->loadRoutesFrom(__DIR__.'/routes/routes.php');
    }
}
