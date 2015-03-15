<?php

namespace Trace;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TraceServiceProvider extends BaseServiceProvider
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
        $this->app->bindShared('trace', function($app)
        {
            return new Trace;
        });

        $this->app->bindShared('trace.instance', function($app)
        {
            return new AwsInstance($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('trace', 'trace.instance');
    }
}
