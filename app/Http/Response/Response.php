<?php

namespace RZP\Http\Response;

use App;
use View;
use Request;
use RZP\Error\Error;
use RZP\Error\ErrorCode;

class Response
{
    protected $app;

    /**
     * Denotes whether response should be jsonp or not.
     */
    protected $jsonp;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    /**
     * In case the callback parameter in the query string
     * is invalid like ?callback=<script>
     * We will use this instead
     */
    const JSONP_FALLBACK_CALLBACK = 'Razorpay.jsonp_callback';

    public function __construct($app)
    {
        $this->app = $app;

        $this->request = $app['request'];

        $this->ba = $app['basicauth'];
    }

    /**
     * Tells the browser that HTTP AUTH is expected
     * and hence to provide basic auth user and pwd
     */
    public function httpAuthExpected()
    {
        //
        // When basicauth fails, then even if request is jsonp,
        // we need to provide non-jsonp response.
        //
        $this->jsonp = false;

        $response = $this->generateJsonErrorResponse(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED);

        $response->header(Header::WWW_AUTHENTICATE, 'Basic realm="Razorpay"');

        return $response;
    }

    public function provideApiKey()
    {
        $response = $this->generateErrorResponse(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_NOT_PROVIDED);

        return $response;
    }

    public function unauthorized($code)
    {
        return $this->generateErrorResponse($code);
    }

    public function routeNotFound()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    public function routeDisabled()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_ROUTE_DISABLED);
    }

    public function httpMethodNotAllowed()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED);
    }

    public function rateLimitExceeded()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_RATE_LIMIT_EXCEEDED);
    }

    public function onlyHttpsAllowed()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED);
    }

    public function oauthInvalidScope()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID);
    }

    public function generateErrorResponse($error, $debug = false)
    {
        list($publicError, $httpStatusCode) = $this->getErrorResponseFields($error, $debug);

        return $this->generateResponse($publicError, $httpStatusCode);
    }

    public function generateJsonErrorResponse($code)
    {
        list($publicError, $httpStatusCode) = $this->getErrorResponseFields($code);

        return $this->json($publicError, $httpStatusCode);
    }

    public function getErrorResponseFields($error, $debug = false)
    {
        $isPublicAuth = $this->ba->isPublicAuth();

        if (($error instanceof Error) === false)
        {
            $error = new Error($error);
        }

        $data = $debug ? $error->toDebugArray() : $error->toPublicArray($isPublicAuth);

        $httpStatusCode = $error->getHttpStatusCode();

        return [$data, $httpStatusCode];
    }

    public function generateResponse($data = array(), $status = 200)
    {
        $app = $this->app;

        $key = 'rzp.merchant_callback_url';

        $route = $this->getCurrentRouteName();

        if ((isset($app[$key])) and
            ($app[$key] !== null))
        {
            if ($this->isMerchantCallbackRoute($route))
            {
                $data = $this->flattenArrayForPost($data);

                $callbackArray = array(
                    'type' => 'return',
                    'request' => [
                        'url' => $app[$key],
                        'method' => 'post',
                        'content' => $data,
                    ],
                );

                $view = \View::make('gateway.callbackReturnUrl')
                            ->with('data', $callbackArray)->render();

                return \Response::make($view);
            }
        }
        else if ($this->isCallbackRoute($route))
        {
            $data['http_status_code'] = $status;

            $view = \View::make('gateway.callback')->with('data', $data)->render();

            return \Response::make($view);
        }
        else if ($this->isCheckoutRoute($route))
        {
            return $this->generateCheckoutView($data);
        }

        return $this->json($data, $status);
    }

    public function json($data = array(), $status = 200)
    {
        $response = \Response::json();

        $route = $this->getCurrentRouteName();

        $this->setContentTypeHtmlForSpecificRoutes($route, $response);
        $this->setAccessControlAllowOriginStarOnSpecificRoutes($route, $response);

        if ($this->isResponseJsonp($route))
        {
            $data['http_status_code'] = $status;
            $status = 200;

            $this->attachJsonpCallback($response);
        }

        $response->setData($data);
        $response->setStatusCode($status);

        $this->stopBrowserCaching($response);

        $this->setSameOriginInHeaders($response, $route);

        return $response;
    }

    protected function isResponseJsonp($route)
    {
        $callback = $this->app['request']->input('callback');

        return (($this->jsonp === null) and
                ($this->isJsonpRoute($route)) and
                ($callback !== null));
    }

    protected function stopBrowserCaching($response)
    {
        //
        // Ask browser not to cache
        //
        $response->headers->set(Header::CACHE_CONTROL,'nocache, no-store, max-age=0, must-revalidate');

        $response->headers->set(Header::PRAGMA,'no-cache');

        //
        // Put old time so that any browser cache gets expired
        //
        $response->headers->set(Header::EXPIRES,'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    /**
     * setCallback can throw an exception in case of an invalid
     * parameter (callback), which is not validated at all. The setCallback
     * call validates it internally and throws an exception. We
     * catch that exception here and make sure that we have a fallback
     * communication mechanism. Checkout ensures that Razorpay.jsonp_callback
     * is defined and works properly.
     */
    protected function attachJsonpCallback($response)
    {
        $callback = $this->app['request']->input('callback');

        try
        {
            $response->setCallback($callback);
        }
        catch (\InvalidArgumentException $e)
        {
            $response->setCallback(self::JSONP_FALLBACK_CALLBACK);
        }
    }

    protected function generateCheckoutView($data)
    {
        $view = \View::make('checkout.checkout')
                     ->with($data)
                     ->render();

        return \Response::make($view);
    }

    protected function isJsonpRequired($path)
    {
        return Route::isJsonpRoute($path);
    }

    protected function isMerchantCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create',
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_redirect_callback'
        );

        return (in_array($route, $callbackRoutes));
    }

    protected function isCallbackRoute($route)
    {
        $callbackRoutes = array(
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_redirect_callback'
        );

        return (in_array($route, $callbackRoutes));
    }

    protected function isCheckoutRoute($route)
    {
        $checkoutRoute = array(
            'checkout');

        return (in_array($route, $checkoutRoute));
    }

    protected function isJsonpRoute($route)
    {
        $jsonpRoutes = array(
            'merchant_checkout_preferences',
            'merchant_methods',
            'merchant_public_get_banks',
            'payment_cancel',
            'payment_create_jsonp',
            'payment_get_status'
        );

        return (in_array($route, $jsonpRoutes));
    }

    protected function setContentTypeHtmlForSpecificRoutes($route, $response)
    {
        $routes = array('payment_create');

        if (in_array($route, $routes))
        {
            //
            // The content-type is set to text/html instead of json
            // because on android 2.* json content is not being read on form
            // post for cards with no 3d-secure.
            //
            $response->headers->set(Header::CONTENT_TYPE, 'text/html; charset=UTF-8');
        }
    }

    protected function setAccessControlAllowOriginStarOnSpecificRoutes($route, $response)
    {
        $routes = [
            'payment_cancel',
            'payment_create_ajax',
            'payment_otp_submit',
            'payment_otp_resend',
            'payment_topup_ajax',
            'merchant_methods_downtime',
        ];

        if (in_array($route, $routes, true) === true)
        {
            //
            // These routes are being hit from razorpay.js which is being called
            // not from our own domain but someone else's. We need to allow for that
            // otherwise these routes will not work there. Read furhter on CORS
            // to understand better.
            //
            $response->headers->set(Header::ACCESS_CONTROL_ALLOW_ORIGIN, '*');
        }
    }

    protected function setSameOriginInHeaders($response, $route)
    {
        if ($this->mustNotSetSameOriginHeaders($route))
        {
            return;
        }

        $response->headers->set(Header::X_FRAME_OPTIONS, 'SAMEORIGIN', false);
    }

    protected function mustNotSetSameOriginHeaders($route)
    {
        $routes = array('checkout');

        return (in_array($route, $routes));
    }

    protected function flattenArrayForPost($data)
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

    protected function getCurrentRouteName()
    {
        return $this->app['api.route']->getCurrentRouteName();
    }
}
