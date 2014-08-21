<?php

namespace EE\Exception;

use App;
use Config;
use Http\ApiResponse;

class Handler
{
    protected $app;

    protected $debug;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->debug = Config::get('app.debug');

        $this->registerExceptionHandlers();
    }

    public function registerExceptionHandlers()
    {
        $this->app->error(function(\Exception $e, $code)
        {
            return $this->genericExceptionHandler($e, $code);
        });

        $this->app->error(function(BaseException $e, $code)
        {
            return $this->baseExceptionHandler($e, $code);
        });
    }

    public function genericExceptionHandler(\Exception $exception)
    {
        $this->traceException($exception);

        //
        // When running in console, throw the exception, irrespective
        // of debug config
        //
        if ($this->app->runningInConsole())
        {
            return;
        }

        //
        // If debug is false, then return standard server error response
        //
        if ($this->debug === false)
        {
            return ApiResponse::serverError();
        }
    }

    public function baseExceptionHandler(BaseException $exception, $code)
    {
        if ($this->debug)
        {
            // ServerError is fatal error and shoudn't be encountered
            // Let the higher-ups handle it. This function handle
            // known/expected exceptions
            if ($exception instanceof ServerErrorException)
                return;

            return $exception->generateDebugJsonResponse();
        }

        return $exception->generatePublicJsonResponse();
    }

    protected function traceException(\Exception $exception)
    {
        $traceData = array(
            'class' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'stack' => $exception->getTraceAsString());

        \Trace\Trace::getInstance()->critical(
           \Trace\TraceCode::ERROR_EXCEPTION,
           $traceData);
    }
}