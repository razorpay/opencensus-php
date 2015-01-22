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
    {// sd($exception->getTraceAsString());
        $this->traceException($exception);

        //
        // When running in console, throw the exception, irrespective
        // of debug config
        //
        if ($this->app->runningInConsole())
        {
            return;
        }

        return ApiResponse::serverError($this->debug, $exception);
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
        $data = '';

        if ($exception instanceof ServerErrorException)
        {
            $data = $exception->getDataAsString();
        }

        $traceData = $this->getExceptionDetails($exception);

        \Trace\Trace::getInstance()->critical(
           \Trace\TraceCode::ERROR_EXCEPTION,
           $traceData);
    }

    protected function getExceptionDetails(\Exception $exception)
    {
        $previousException = $exception->getPrevious();

        $previous = ($previousException !== null) ? $this->getExceptionDetails($previousException) : null;

        $data = null;

        if (method_exists($exception, 'getData'))
        {
            $data = $exception->getData();
        }

        //
        // @note: Always call function 'getTraceAsSring' to get stack trace
        //        since it doesn't include function arguments.
        //        Function arguments can contain sensitive data so should
        //        never be logged. Never call 'getTrace' directly.
        //
        $traceData = array(
            'class'     => get_class($exception),
            'code'      => $exception->getCode(),
            'message'   => $exception->getMessage(),
            'data'      => $data,
            'stack'     => $exception->getTraceAsString(),
            'previous'  => $previous);
    }
}