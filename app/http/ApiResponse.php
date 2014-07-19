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
        return self::generateResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
    }

    public static function routeNotFound()
    {
        return self::generateResponse(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    public static function stopBrowserCaching($request, $response)
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

    public static function generateResponse($code)
    {
        $error = new Error($code);

        $publicError = $error->getPublicError();

        $httpStatusCode = $publicError->getHttpStatusCode();

        return Response::json($publicError->toArray(), $httpStatusCode);
    }
}