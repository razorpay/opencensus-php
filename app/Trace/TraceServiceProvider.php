<?php

namespace RZP\Trace;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TraceServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind('trace', function($app)
        {
            $trace = new Trace($app);

            $trace->init();

            return $trace;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['trace'];
    }
}
