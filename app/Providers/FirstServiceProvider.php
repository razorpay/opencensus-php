<?php

namespace RZP\Providers;

use Config;
use Barryvdh\Debugbar;
use RZP\Metro\MetroHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

use RZP\Diag;
use RZP\Models\P2p;
use RZP\Jobs\Context;
use RZP\Http\RequestContext;
use RZP\Http\RequestContextV2;
use RZP\Trace\ApiTraceProcessor;

class FirstServiceProvider extends ServiceProvider
{
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

        $this->registerWorkerContext();

        $this->registerP2pContext();

        $this->registerReqContext();

        $this->registerMetroClient();
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
        $this->app->singleton('request.ctx.v2', function($app) { return new RequestContextV2(); });
    }

    protected function registerWorkerContext()
    {
        $this->app->singleton('worker.ctx', function($app) { return new Context($app); });
    }

    protected function registerP2pContext()
    {
        $this->app->singleton('p2p.ctx', function($app) { return new P2p\Base\Libraries\Context($app); });
    }

    protected function registerReqContext()
    {
        $this->app->singleton('req.context', function() { return new Diag\ReqContext(); });
    }

    protected function registerMetroClient()
    {
        $this->app->singleton('metro', function() {
            return new MetroHandler();
        });
    }
}
