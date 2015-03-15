<?php

namespace Exception;

use App;
use Config;
use Trace;
use Redirect;
use Response;

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
    }

    public function genericExceptionHandler(\Exception $exception, $code)
    {   
        

        if ($code === 404)
        {
            return Redirect::to('/#/404');
        }
        else
        {
            $this->traceException($exception);

            if ($this->debug === false)
            {
                return Response::json(array('success' => false, 'errors' => ['Internal Server Error']));
            }
            else
            {
                return;
            }
        }

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
}