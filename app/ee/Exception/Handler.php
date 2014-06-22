<?php

namespace EE\Exception;

use App;
use Config;
use Response;
use PrettyPageHandler;

use EE\Error\Error;
use EE\Error\ErrorCode;

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

        //
        // Register gateway timeout exception handler
        //
        App::error(function(GatewayTimeoutException $e, $code)
        {
            return $this->gatewayTimeoutExceptionHandler($e, $code);
        });

        //
        // Register gateway timeout exception handler
        //
        App::error(function(InvalidCardException $e, $code)
        {
            return $this->invalidCardExceptionHandler($e, $code);
        });
    }

    public function whoopsExceptionDisplayHandler()
    {
        // Use the Laravel IoC container to get the Whoops\Run instance, if whoops
        // is available (which will be the case, by default, in the dev
        // environment)

        if((App::bound('whoops')) and
           (Config::get('app.debug')))
        {
            // Retrieve the whoops handler in charge of displaying exceptions:
            $whoopsDisplayHandler = App::make("whoops.handler");

            // Laravel will use the PrettyPageHandler by default, unless this
            // is an AJAX request, in which case it'll use the JsonResponseHandler:
            if($whoopsDisplayHandler instanceof PrettyPageHandler)
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

    public function gatewayTimeoutExceptionHandler(GatewayTimeoutException $exception, $code)
    {
        return $exception->generateJsonResponse();
    }

    public function invalidCardExceptionHandler(InvalidCardException $exception, $code)
    {
        return $exception->generateJsonResponse();
    }

    public function generateJsonResponse(\Exception $exception)
    {
        $error = $exception->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return Response::json($error->toArray(), $httpStatusCode);
    }
}