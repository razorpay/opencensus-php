<?php

namespace EE\Exception;

use App;
use Config;
use Http\ApiResponse;
use PrettyPageHandler;

class Handler
{
    public function __construct()
    {
        $this->registerExceptionHandlers();
    }

    public function registerExceptionHandlers()
    {
        //
        // Register whoops display handler
        //
        App::error(function(\Exception $e, $code)
        {
            return $this->whoopsExceptionDisplayHandler();
        });

        App::error(function(\Exception $e, $code)
        {
            return $this->genericExceptionHandler($e, $code);
        });

        //
        // Register base gateway exception handler
        //
        App::error(function(BaseException $e, $code)
        {
            return $this->baseExceptionHandler($e, $code);
        });

        App::error(function(ServerErrorException $e, $code)
        {
            return $this->serverErrorExceptionHandler($e, $code);
        });
    }

    public function whoopsExceptionDisplayHandler()
    {
        // Use the Laravel IoC container to get the Whoops\Run instance, if whoops
        // is available (which will be the case, by default, in the dev
        // environment)

        if ((App::bound('whoops')) and
           (Config::get('app.debug')))
        {
            // Retrieve the whoops handler in charge of displaying exceptions:
            $whoopsDisplayHandler = App::make("whoops.handler");

            // Laravel will use the PrettyPageHandler by default, unless this
            // is an AJAX request, in which case it'll use the JsonResponseHandler:
            if ($whoopsDisplayHandler instanceof PrettyPageHandler)
            {
                // Set a custom page title for our error page:
                $whoopsDisplayHandler->setPageTitle("Mayday! Mayday! Don't push the code!");

                // Set the "open:" link for files to our editor of choice:
                $whoopsDisplayHandler->setEditor("sublime");

                $records = Trace\Trace::getInstance()->getFlattenedRecordsForScreen();

                $whoopsDisplayHandler->addDataTable('Trace', $records);
            }
        }
    }

    public function genericExceptionHandler(\Exception $exception)
    {
        $this->traceException($exception);

        if (Config::get('app.debug') === false)
        {
            return ApiResponse::serverError();
        }
    }

    public function baseExceptionHandler(BaseException $exception, $code)
    {
        if (Config::get('app.debug'))
        {
            //
            // Throw ServerErrorException (internal server errors)
            // for 'dev' environment to help debugging.
            // For non-dev, only public json is shown.
            // @todo: log exception for non-dev environments;
            //
            if ($exception instanceof ServerErrorException)
                return;

            return $exception->generateDebugJsonResponse();
        }

        return $exception->generatePublicJsonResponse();
    }

    public function serverErrorExceptionHandler(ServerErrorException $exception, $code)
    {
        return;
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