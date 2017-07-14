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

        $this->registerRequestGenerateIdMacro();

        $this->registerRequestGetIdMacro();

        $this->registerRequestGenerateTaskIdMacro();

        $this->registerRequestGetTaskIdMacro();

        $this->registerRequestGetClientIpMacro();

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
        $request = $this->app['request'];

        $request->macro('getId', function() use ($request)
        {
            if ($this->requestId === null)
            {
                $this->requestId = $request->generateId();
            }

            return $this->requestId;
        });
    }

    protected function registerRequestGenerateIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('generateId', function()
        {
            $this->requestId = bin2hex(openssl_random_pseudo_bytes(16));

            return $this->requestId;
        });
    }

    protected function registerRequestGetTaskIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('getTaskId', function () use ($request)
        {
            if ($this->taskId === null)
            {
                // For task id if nothing is set we check the X-Razorpay-TaskId header
                // value before generating our own task id
                $taskIdHeader = $this->headers->get('X-Razorpay-TaskId');

                $this->taskId = $request->generateTaskId($taskIdHeader);
            }

            return $this->taskId;
        });
    }

    protected function registerRequestGenerateTaskIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('generateTaskId', function ($taskId = null)
        {
            if ($taskId === null)
            {
                $this->taskId = bin2hex(openssl_random_pseudo_bytes(16));
            }
            else
            {
                $this->taskId = $taskId;
            }

            return $this->taskId;
        });
    }
}
