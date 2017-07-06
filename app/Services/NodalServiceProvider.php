<?php

namespace RZP\Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class NodalServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('nodal-service', function($app)
        {
            return new Nodal($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('nodal-service');
    }
}
