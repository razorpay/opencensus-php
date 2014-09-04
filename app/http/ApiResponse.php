<?php

namespace Http;

use EE\Error\Error;
use EE\Error\ErrorCode;
use Response;

class ApiResponse
{
    protected static $jsonp = null;

    /**
     * Tells the browser that HTTP AUTH is expected
     * and hence to provide basic auth user and pwd
     */
    public static function httpAuthExpected()
    {
        self::$jsonp = false;

        $response = self::generateResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED);

        $response->header('WWW-Authenticate', 'Basic realm="Razorpay"');

        return $response;
    }

    public static function unauthorized($code)
    {
        return self::generateResponse($code);
    }

    public static function routeNotFound()
    {
        return self::generateResponse(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    public static function stopBrowserCaching($response)
    {
        //
        // Ask browser not to cache
        //
        $response->headers->set('Cache-Control','nocache, no-store, max-age=0, must-revalidate');

        $response->headers->set('Pragma','no-cache');

        //
        // Put old time so that any browser cache gets expired
        //
        $response->headers->set('Expires','Fri, 01 Jan 1990 00:00:00 GMT');
    }

    protected static function attachJsonpCallback($request, $response)
    {
        $callback = $request->input('callback');

        $response->setCallback($callback);
    }

    public static function generateResponse($code)
    {
        list($publicError, $httpStatusCode) = self::getErrorResponseFields($code);

        return self::json($publicError, $httpStatusCode);
    }

    public static function getErrorResponseFields($code)
    {
        $error = new Error($code);

        $publicError = $error->toPublicArray();

        $httpStatusCode = $error->getHttpStatusCode();

        return array($publicError, $httpStatusCode);
    }

    public static function serverError($debug, $exception = null)
    {
        list($publicError, $httpStatusCode) =
                self::getErrorResponseFields(ErrorCode::SERVER_ERROR);

        if (($debug) and
            ($exception !== null))
        {
            $exceptionArr['message'] = $exception->getMessage();
            $exceptionArr['code'] = $exception->getCode();
            $exceptionArr['file'] = $exception->getFile();
            $exceptionArr['line'] = $exception->getLine();
            $exceptionArr['trace'] = $exception->getTrace();

            $publicError['exception'] = $exceptionArr;
        }

        return self::json($publicError, $httpStatusCode);
    }

    protected static function debugException($e)
    {
        return self::generateResponse(ErrorCode::SERVER_ERROR);
    }

    public static function json($data = array(), $status = 200)
    {
        $request = \Request::getFacadeRoot();

        $jsonp = false;

        $path = $request->path();

        if (self::isJsonpRequired($path))
        {
            $data['http_status_code'] = $status;

            $status = 200;
        }

        $response = Response::json($data, $status);

        if (self::$jsonp)
        {
            self::attachJsonpCallback($request, $response);
        }

        self::stopBrowserCaching($response);

        return $response;
    }

    protected static function isJsonpRequired($path)
    {
        self::$jsonp = ((self::$jsonp !== false) and
                        (Route::isJsonpRoute($path)));

        return self::$jsonp;
    }
}