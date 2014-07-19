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
        $error = new Error(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

        $error = $error->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return Response::json($error->toArray(), 401)
                       ->header('WWW-Authenticate', 'Basic realm="Protected Area"');
    }

    public static function routeNotFound()
    {
        $error = new Error(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

        $error = $error->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return Response::json($error->toArray(), $httpStatusCode);
    }

    public static function setHeaders($request, $response)
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
}