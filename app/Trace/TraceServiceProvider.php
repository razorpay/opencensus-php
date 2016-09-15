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
        //
        // We need to register this macro here
        // because immediately after it's being used
        // in trace constructor
        //

        $this->registerRequestGetIdMacro();

        $this->registerRequestGetClientIpMacro();

        $this->app->singleton('trace', function($app)
        {
            return new Trace($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('trace');
    }

    protected function registerRequestGetClientIpMacro()
    {
        $this->app['request']->macro('getRealClientIp', function()
        {
            $clientIp = $this->headers->get('X_FORWARDED_FOR');

            if ($clientIp === null)
            {
                $clientIp = $this->getClientIp();
            }

            return $clientIp;
        });

    }

    protected function registerRequestGetIdMacro()
    {
        $this->app['request']->macro('getId', function()
        {
            if ($this->requestId === null)
            {
                $this->requestId = bin2hex(openssl_random_pseudo_bytes(16));
            }

            return $this->requestId;
        });
    }

}