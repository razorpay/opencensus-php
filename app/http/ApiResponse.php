<?php

namespace Http;

use App;
use EE\Error\Error;
use EE\Error\ErrorCode;
use Request;
use Response;
use View;

class ApiResponse
{
    protected static $jsonp;

    /**
     * Tells the browser that HTTP AUTH is expected
     * and hence to provide basic auth user and pwd
     */
    public static function httpAuthExpected()
    {
        self::$jsonp = false;

        $response = self::generateJsonErrorResponse(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED);

        $response->header('WWW-Authenticate', 'Basic realm="Razorpay"');

        return $response;
    }

    public static function provideApiKey()
    {
        $response = self::generateErrorResponse(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_NOT_PROVIDED);

        return $response;
    }

    public static function unauthorized($code)
    {
        return self::generateErrorResponse($code);
    }

    public static function routeNotFound()
    {
        return self::generateErrorResponse(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    public static function httpMethodNotAllowed()
    {
        return self::generateErrorResponse(ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED);
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

    public static function generateErrorResponse($code)
    {
        list($publicError, $httpStatusCode) = self::getErrorResponseFields($code);

        return self::generateResponse($publicError, $httpStatusCode);
    }

    public static function generateJsonErrorResponse($code)
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
            $publicError['exception'] = self::getExceptionData($exception);

            if (method_exists($exception, 'getData'))
            {
                $publicError['data'] = $exception->getData();
            }
        }

        return self::generateResponse($publicError, $httpStatusCode);
    }

    public static function recoverableError($debug, $exception = null)
    {
        $error = $exception->getError();

        $httpStatusCode = $error->getHttpStatusCode();

        $data = $debug ? $error->toDebugArray() : $error->toPublicArray();

        return self::generateResponse($data, $httpStatusCode);
    }

    protected static function getExceptionData($exception)
    {
        $previous = $exception->getPrevious();
        $previousData = null;

        if ($previous !== null)
            $previousData = self::getExceptionData($previous);

        $data = array(
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $previousData,
        );

        $data['trace'] = str_replace('/', "\\", $data['trace']);
        $data['file'] = str_replace('/', "\\", $data['file']);

        return $data;
    }

    protected static function debugException($e)
    {
        return self::generateErrorResponse(ErrorCode::SERVER_ERROR);
    }

    protected static function generateResponse($data = array(), $status = 200)
    {
        $app = \App::getFacadeRoot();

        $key = 'rzp.merchant_callback_url';

        $router = $app['router'];

        $route = $router->currentRouteName();

        if ((isset($app[$key])) and
            ($app[$key] !== null))
        {
            if (self::isMerchantCallbackRoute($route))
            {
                $data = self::flattenArrayForPost($data);

                $callbackArray = array(
                    'type' => 'return',
                    'request' => [
                        'url' => $app[$key],
                        'method' => 'post',
                        'content' => $data,
                    ],
                );

                return \View::make('gateway.callbackReturnUrl')
                            ->with('data', $callbackArray);
            }
        }
        else if (self::isCallbackRoute($route))
        {
            $data['http_status_code'] = $status;

            return \View::make('gateway.callback')->with('data', $data);
        }

        return self::json($data, $status);
    }

    public static function json($data = array(), $status = 200)
    {
        $request = \Request::getFacadeRoot();

        $response = Response::json();

        $app = \App::getFacadeRoot();
        $router = $app['router'];
        $route = $router->currentRouteName();

        self::setContentTypeHtmlForSpecificRoutes($route, $response);

        if ((self::$jsonp === null) and
            (self::isJsonpRoute($route)))
        {
            $data['http_status_code'] = $status;
            $status = 200;

            self::attachJsonpCallback($request, $response);
        }

        $response->setData($data);
        $response->setStatusCode($status);

        self::stopBrowserCaching($response);

        self::setSameOriginInHeaders($response, $route);

        // This statement is needed for keeping tests functional since
        // we are using a static var here @todo: change this!
        self::$jsonp = null;

        return $response;
    }

    protected static function isJsonpRequired($path)
    {
        return Route::isJsonpRoute($path);
    }

    protected static function isMerchantCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
        );

        return (in_array($route, $callbackRoutes));
    }

    protected static function isCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
        );

        return (in_array($route, $callbackRoutes));
    }

    protected static function isJsonpRoute($route)
    {
        $jsonpRoutes = array(
            'checkout',
            'merchant_methods',
            'merchant_public_get_banks',
            'payment_cancel',
            'payment_create_jsonp',
        );

        return (in_array($route, $jsonpRoutes));
    }

    protected static function setContentTypeHtmlForSpecificRoutes($route, $response)
    {
        $routes = array('payment_create');

        if (in_array($route, $routes))
        {
            //
            // The content-type is set to text/html instead of json
            // because on android 2.* json content is not being read on form
            // post for cards with no 3d-secure.
            //
            $response->headers->set('content-type', 'text/html; charset=UTF-8');
        }
    }

    public static function setSameOriginInHeaders($response, $route)
    {
        if (self::mustNotSetSameOriginHeaders($route))
        {
            return;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
    }

    protected static function mustNotSetSameOriginHeaders($route)
    {
        $routes = array('checkout');

        return (in_array($route, $routes));
    }

    protected static function flattenArrayForPost($data)
    {
        $data = flatten_array($data, '][');

        $array = [];

        foreach ($data as $key => $value)
        {
            $key = preg_replace('/\]\[/', '[', $key, 1) . ']';
            $array[$key] = $value;
        }

        return $array;
    }
}