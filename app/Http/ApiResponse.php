<?php

namespace RZP\Http;

use App;
use View;
use Request;
use Response;
use BasicAuth;
use RZP\Error\Error;
use RZP\Error\ErrorCode;

class ApiResponse
{
    protected static $jsonp;

    /**
     * In case the callback parameter in the query string
     * is invalid like ?callback=<script>
     * We will use this instead
     */
    const JSONP_FALLBACK_CALLBACK = 'Razorpay.jsonp_callback';

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

    public static function onlyHttpsAllowed()
    {
        return self::generateErrorResponse(ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED);
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

    /**
     * setCallback can throw an exception in case of an invalid
     * parameter (callback), which is not validated at all. The setCallback
     * call validates it internally and throws an exception. We
     * catch that exception here and make sure that we have a fallback
     * communication mechanism. Checkout ensures that Razorpay.jsonp_callback
     * is defined and works properly.
     */
    protected static function attachJsonpCallback($request, $response)
    {
        $callback = $request->input('callback');

        try
        {
            $response->setCallback($callback);
        }
        catch(\InvalidArgumentException $e)
        {
            $response->setCallback(self::JSONP_FALLBACK_CALLBACK);
        }
    }

    public static function generateErrorResponse($error, $debug = false)
    {
        list($publicError, $httpStatusCode) = self::getErrorResponseFields($error, $debug);

        return self::generateResponse($publicError, $httpStatusCode);
    }

    public static function generateJsonErrorResponse($code)
    {
        list($publicError, $httpStatusCode) = self::getErrorResponseFields($code);

        return self::json($publicError, $httpStatusCode);
    }

    public static function getErrorResponseFields($error, $debug = false)
    {
        $isPublicAuth = BasicAuth::isPublicAuth();

        if (($error instanceof Error) === false)
        {
            $error = new Error($error);
        }

        $data = $debug ? $error->toDebugArray() : $error->toPublicArray($isPublicAuth);

        $httpStatusCode = $error->getHttpStatusCode();

        return [$data, $httpStatusCode];
    }

    protected static function debugException($e)
    {
        return self::generateErrorResponse(ErrorCode::SERVER_ERROR);
    }

    public static function generateResponse($data = array(), $status = 200)
    {
        $app = App::getFacadeRoot();

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
        else if (self::isCheckoutRoute($route))
        {
            return self::generateCheckoutView($data);
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
        self::setAccessControlAllowOriginStarOnSpecificRoutes($route, $response);

        $callback = $request->input('callback');

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

    protected static function generateCheckoutView($data)
    {
        if (isset($data['font']) === false)
        {
            $data['font'] = 'https://cdn.razorpay.com/lato2';
        }

        return \View::make('checkout.checkout')
                    ->with($data);
    }

    protected static function isJsonpRequired($path)
    {
        return Route::isJsonpRoute($path);
    }

    protected static function isMerchantCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create',
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_redirect'
        );

        return (in_array($route, $callbackRoutes));
    }

    protected static function isCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_redirect'
        );

        return (in_array($route, $callbackRoutes));
    }

    protected static function isCheckoutRoute($route)
    {
        $checkoutRoute = array(
            'checkout');

        return (in_array($route, $checkoutRoute));
    }

    protected static function isJsonpRoute($route)
    {
        $jsonpRoutes = array(
            'merchant_checkout_preferences',
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

    protected static function setAccessControlAllowOriginStarOnSpecificRoutes($route, $response)
    {
        $routes = array(
            'payment_cancel',
            'payment_create_ajax',
            'payment_otp_submit',
            'payment_otp_resend',
            'payment_topup_ajax');

        if (in_array($route, $routes))
        {
            //
            // These routes are being hit from razorpay.js which is being called
            // not from our own domain but someone else's. We need to allow for that
            // otherwise these routes will not work there. Read furhter on CORS
            // to understand better.
            //
            $response->headers->set('Access-Control-Allow-Origin', '*');
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
