<?php namespace Huludini\PerfectWorldAPI;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->publishFiles();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Publish files for the package.
     *
     * @return void
     */
    protected function publishFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/pw-api.php' => config_path('pw-api.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../lang' => base_path('resources/lang'),
        ]);
    }

}