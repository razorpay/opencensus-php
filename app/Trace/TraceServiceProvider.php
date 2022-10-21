<?php

namespace App\Trace;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TraceServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    protected string $requestId;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerRequestGetIdMacro();

        $this->app->singleton('trace', function()
        {
            return new Trace();
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

    protected function registerRequestGetIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('generateId', function()
        {
            $this->requestId = bin2hex(openssl_random_pseudo_bytes(16));

            return $this->requestId;
        });

        $request->macro('getId', function() use($request)
        {
            if ($this->requestId === null)
            {
                $this->requestId = $request->generateId();
            }

            return $this->requestId;
        });
    }
}
