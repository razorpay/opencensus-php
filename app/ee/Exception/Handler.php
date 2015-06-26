<?php

namespace EE\Exception;

use App;
use Config;
use Http\ApiResponse;
use Trace;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class Handler
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

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

        $this->app->error(function(MethodNotAllowedHttpException $e)
        {
            return ApiResponse::httpMethodNotAllowed();
        });

        $this->app->error(function(ProcessTimedOutException $e)
        {
            return ApiResponse::json(['erorr' => 'Process timed out']);
        });
    }

    public function genericExceptionHandler(\Exception $exception)
    {// sd($exception->getTraceAsString());
        $this->traceException($exception);

        //
        // When running in console, throw the exception, irrespective
        // of debug config
        //
        if (($this->app->runningInConsole()) and
            ($this->app->environment('testing') === false))
        {
            return;
        }

        return ApiResponse::serverError($this->isDebug(), $exception);
    }

    public function baseExceptionHandler(BaseException $exception, $code)
    {
        // ServerError is fatal error and shoudn't be encountered
        // Let the higher-ups handle it. This function handles
        // known/expected exceptions
        if ($exception instanceof ServerErrorException)
            return;

        return ApiResponse::recoverableError($this->isDebug(), $exception);
    }

    protected function traceException(\Exception $exception)
    {
        $traceData = $this->getExceptionDetails($exception);

        $this->app['trace']->critical(
           Trace\TraceCode::ERROR_EXCEPTION,
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
        // @note: Don't remove this comment.
        //
        $traceData = array(
            'class'     => get_class($exception),
            'code'      => $exception->getCode(),
            'message'   => $exception->getMessage(),
            'data'      => $data,
            'stack'     => $exception->getTraceAsString(),
            'previous'  => $previous);

        return $traceData;
    }

    protected function isDebug()
    {
        return $this->app['config']->get('app.debug');
    }
}