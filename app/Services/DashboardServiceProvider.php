<?php

namespace RZP\Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RZP\Dashboard\Dashboard;

class DashboardServiceProvider extends BaseServiceProvider
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
        $this->app->singleton('dashboard', function($app)
        {
            return new Dashboard($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('dashboard');
    }
}
