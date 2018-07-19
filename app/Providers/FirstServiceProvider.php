<?php

namespace RZP\Providers;

use Config;
use Metrics;
use Barryvdh\Debugbar;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

use RZP\Http\RequestContext;
use RZP\Trace\ApiTraceProcessor;

class FirstServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDebugbarIfApplicable();

        $this->registerRequestGetIdMacro();

        $this->registerRequestSetTaskIdMacro();

        $this->registerRequestGetTaskIdMacro();

        $this->registerRequestContext();

        $this->registerTraceMetricMacros();
    }

    public function boot()
    {
        $this->registerValidatorResolver();
    }

    /**
     * Registers getId macro on request to get a new request id to identify
     * the given request in trace logs. Generates a new request id if not already set
     */
    protected function registerRequestGetIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('generateId', function()
        {
            $this->requestId = bin2hex(random_bytes(16));

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

    protected function registerValidatorResolver()
    {
        $this->app['validator']->resolver(
            function($translator, $data, $rules, $messages, $customAttributes)
        {
            return new \RZP\Models\Base\ExtendedValidations(
                            $translator, $data, $rules, $messages, $customAttributes);
        });
    }

    /**
     * Register Debugbar ServiceProvider and Facade for API Inspector
     * for debug mode, non-production requests.
     */
    protected function registerDebugbarIfApplicable()
    {
        if ((Config::get('app.debug') === true) and
            ($this->app->environment() !== 'production'))
        {
            $this->app->register(Debugbar\ServiceProvider::class);
            AliasLoader::getInstance()->alias('Debugbar', Debugbar\Facade::class);
        }
    }

    /**
     * Registers a getTaskId macro on request. It uses the X-Razorpay-TaskId header value
     * if present, else generates a new one.If api is the source of new task id, then uses
     * the request id value instead of generating new one
     */
    protected function registerRequestGetTaskIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('getTaskId', function () use ($request)
        {
            if ($this->taskId === null)
            {
                // For task id if nothing is set we check the X-Razorpay-TaskId
                // header value. Otherwise, simply copy the request id to task id.
                $taskIdHeader = $this->headers->get('X-Razorpay-TaskId');

                $this->taskId = $taskIdHeader ?? $this->generateId();
            }

            return $this->taskId;
        });
    }

    protected function registerRequestSetTaskIdMacro()
    {
        $request = $this->app['request'];

        $request->macro('setTaskId', function ($taskId)
        {
            $this->taskId = $taskId;

            return $this->taskId;
        });
    }

    protected function registerRequestContext()
    {
        $this->app->singleton('request.ctx', function($app) { return new RequestContext($app); });
    }

    /**
     * Registers metric methods(as macros) on trace instance
     */
    protected function registerTraceMetricMacros()
    {
        $trace = $this->app['trace'];

        $trace->macro('incr', function (string $metric, array $dimensions = [])
        {
            Metrics::count($metric, 1, $dimensions);
        });

        $trace->macro('count', function (string $metric, int $times = 1, array $dimensions = [])
        {
            Metrics::count($metric, $times, $dimensions);
        });

        $trace->macro('gauge', function (string $metric, float $value, array $dimensions = [])
        {
            Metrics::gauge($metric, $value, $dimensions);
        });

        $trace->macro('histogram', function (string $metric, float $value, array $dimensions = [])
        {
            Metrics::histogram($metric, $value, $dimensions);
        });

        $trace->macro('summary', function (string $metric, float $value, array $dimensions = [])
        {
            Metrics::summary($metric, $value, $dimensions);
        });
    }
}
