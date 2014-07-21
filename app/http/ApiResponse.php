<?php

namespace Http;

use EE\Error\Error;
use EE\Error\ErrorCode;
use Response;

class ApiResponse
{
    /**
     * Tells the browser that HTTP AUTH is expected
     * and hence to provide basic auth user and pwd
     */
    public static function httpAuthExpected()
    {
        $response = self::generateResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        return $response->header('WWW-Authenticate', 'Basic realm="Protected Area"');
    }

    public static function unauthorized($code = ErrorCode::BAD_REQUEST_UNAUTHORIZED)
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

    protected static function isJsonpUrl($request)
    {
        $urlSegment = $request->path();

        return Url::isJsonpUrl($urlSegment);
    }

    protected static function attachJsonpCallback($request, $response)
    {
        $callback = $request->input('callback');
        $callback = 'dfsdfsdf';
        $response->setCallback($callback);
    }

    public static function generateResponse($code)
    {
        $error = new Error($code);

        $publicError = $error->getPublicError();

        $httpStatusCode = $publicError->getHttpStatusCode();

        return self::json($publicError->toArray(), $httpStatusCode);
    }

    public static function serverError()
    {
        return self::generateResponse(ErrorCode::SERVER_ERROR);
    }

    public static function json($data = array(), $status = 200)
    {
        $request = \Request::getFacadeRoot();

        if (self::isJsonpUrl($request))
        {
            $data['http_status_code'] = $status;

            $status = 200;
        }

        $response = Response::json($data, $status);

        if (self::isJsonpUrl($request))
        {
            self::attachJsonpCallback($request, $response);
        }

        self::stopBrowserCaching($response);

        return $response;
    }
}