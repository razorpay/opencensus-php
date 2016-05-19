<?php

namespace Exception;

use App;
use Config;
use Trace;
use Redirect;
use Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler
{
    const PHP7_500_ERROR     = 'Internal Server Error.';
    const SERVER_ERROR       = 'Internal Server Error';
    const METHOD_NOT_ALLOWED = 'Method not allowed';

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

        $this->app->error(function(MethodNotAllowedHttpException $e)
        {
            return Response::json(array('success' => false, 'errors' => [self::METHOD_NOT_ALLOWED]));
        });

        $this->app->error(function(NotFoundHttpException $e)
        {
            return Redirect::to('/#/404');
        });

        if (PHP_MAJOR_VERSION >=7)
        {
            $this->app->error(function(\Throwable $e, $code)
            {
                return $this->PHP7ExceptionHandler($e, $code);
            });
        }
    }

    public function PHP7ExceptionHandler(\Throwable $e, $code)
    {
        $data = [
            'success' => false,
            'errors'  => [self::PHP7_500_ERROR]
        ];

        if ($this->debug === true)
        {
            $data['details'] = $this->getExceptionDetails($e);
        }

        return Response::json($data);
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
            $data = [
                'success' => false,
                'errors'  => [self::SERVER_ERROR]
            ];

            if ($this->debug === true)
            {
                $data['details'] = $this->getExceptionDetails($exception);
            }

            return Response::json($data);
        }
    }

    protected function traceException(\Exception $exception)
    {
        $traceData = $this->getExceptionDetails($exception);

        Trace::critical(
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
