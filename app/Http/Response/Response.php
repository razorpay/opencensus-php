<?php

namespace RZP\Http\Response;

use App;
use RZP\Http\CheckoutView;
use RZP\Trace\TraceCode;
use View;
use Request;
use RZP\Http\Route;
use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Constants\Environment;
use Illuminate\Http\JsonResponse;

class Response
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
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

        $this->route = $app['api.route'];
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

    public function featurePermissionNotFound()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_FEATURE_PERMISSION_NOT_FOUND);
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

    public function requestBlocked()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_FORBIDDEN);
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

        return $this->generateResponse($publicError, $httpStatusCode, $debug);
    }

    public function generateNachNbErrorResponse($error, $viewData, $debug = false)
    {
        list($publicError, $status) = $this->getErrorResponseFields($error, $debug);

        $app = $this->app;

        $key = 'rzp.merchant_callback_url';

        $route = $this->getCurrentRouteName();

        $merchant = $this->app['basicauth']->getMerchant();

        $viewData += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if (empty($app[$key]) === false)
        {
            if ($this->isMerchantCallbackRoute($route))
            {
                $publicError = $this->flattenArrayForPost($publicError);

                $callbackArray = [
                    'type' => 'return',
                    'request' => [
                        'url'     => $app[$key],
                        'method'  => 'post',
                        'content' => $publicError,
                    ],
                ];

                $viewData = array_merge($callbackArray, $viewData);

                $view = \View::make('gateway.callbackNachNb')->with('data', $viewData)->render();

                return \Response::make($view);
            }
        }
        else if ($this->isCheckoutCallbackRoute($route))
        {
            $data['http_status_code'] = $status;

            $data = array_merge($data, $publicError, $viewData);

            $view = \View::make('gateway.callbackNachNb')->with('data', $data)->render();

            return \Response::make($view);
        }

        return $this->json($publicError, $status, $debug);
    }

    public function generateEmandateNpciErrorResponse($error, $viewData, $debug = false)
    {
        list($publicError, $status) = $this->getErrorResponseFields($error, $debug);

        $app = $this->app;

        $key = 'rzp.merchant_callback_url';

        $route = $this->getCurrentRouteName();

        $merchant = $this->app['basicauth']->getMerchant();

        $viewData += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if (empty($app[$key]) === false)
        {
            if ($this->isMerchantCallbackRoute($route))
            {
                $publicError = $this->flattenArrayForPost($publicError);

                $callbackArray = [
                    'type' => 'return',
                    'request' => [
                        'url'     => $app[$key],
                        'method'  => 'post',
                        'content' => $publicError,
                    ],
                ];

                $viewData = array_merge($callbackArray, $viewData);

                $view = \View::make('gateway.emandateCallbackReturnUrl')->with('data', $viewData)->render();

                return \Response::make($view);
            }
        }
        else if ($this->isCheckoutCallbackRoute($route))
        {
            $data['http_status_code'] = $status;

            $data = array_merge($data, $publicError, $viewData);

            $view = \View::make('gateway.emandateCallback')->with('data', $data)->render();

            return \Response::make($view);
        }

        return $this->json($publicError, $status, $debug);
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

    public function generateResponse($data = [], $status = 200, $debug = false)
    {
        $app = $this->app;

        $key = 'rzp.merchant_callback_url';

        $route = $this->getCurrentRouteName();

        $merchant = $this->app['basicauth']->getMerchant();

        if (empty($app[$key]) === false)
        {
            if ($this->isMerchantCallbackRoute($route))
            {
                $paymentId = $data['error']['metadata']['payment_id'] ?? null;

                if (isset($data['error'], $data['error']['metadata']) === true)
                {
                    $data['error']['metadata'] = json_encode($data['error']['metadata'], JSON_FORCE_OBJECT);
                }

                $data = $this->flattenArrayForPost($data);

                $callbackArray = [
                    'type' => 'return',
                    'request' => [
                        'url' => $app[$key],
                        'method' => 'post',
                        'content' => $data,
                    ],
                ];

                $paymentService = new Payment\Service();

                if (
                    $paymentId !== null &&
                    $paymentService->isEmailLessCheckoutExperimentEnabled($merchant->getId())
                )
                {
                    $paymentDetails = $paymentService->getPaymentDetailsForMerchantRedirectView($paymentId);

                    if (empty($paymentDetails) === false)
                    {
                        $callbackArray['payment_details'] = $paymentDetails;
                    }
                }

                $callbackArray += (new CheckoutView())->addOrgInformationInResponse($merchant);

                $app['trace']->info(TraceCode::CALLBACK_ROUTE_TEMPLATE, [
                    'callbackRoute' => 'Merchant',
                    'template' => 'callbackReturnUrl'
                ]);

                $view = \View::make('gateway.callbackReturnUrl')
                            ->with('data', $callbackArray)->render();

                return \Response::make($view);
            }
        }
        else if ($this->isCheckoutCallbackRoute($route))
        {
            $data['http_status_code'] = $status;

            $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

            $app['trace']->info(TraceCode::CALLBACK_ROUTE_TEMPLATE, [
                'callbackRoute' => 'Checkout',
                'template' => 'callback'
            ]);

            $view = \View::make('gateway.callback')->with('data', $data)->render();

            return \Response::make($view);
        }
        else if ($this->isCheckoutRoute($route))
        {
            return $this->generateCheckoutView($data);
        }
        else if ($this->isViewRoute($route))
        {
            return $this->generateDefaultErrorView($data);
        }

        return $this->json($data, $status, $debug);
    }

    public function json($data = [], $status = 200, $debug = false)
    {
        $response = \Response::json();

        $route = $this->getCurrentRouteName();

        $this->setAccessControlAllowOriginStarOnSpecificRoutes($route, $response);

        $this->setAccessControlAllowCredentialsTrueOnSpecificRoutes($route, $response);

        $this->setAccessControlAllowHeadersStarOnSpecificRoutes($route, $response);

        if ($this->isResponseJsonp($route))
        {
            $data['http_status_code'] = $status;
            $status = 200;

            $this->attachJsonpCallback($response);
        }

        $response->setData($data);
        $response->setStatusCode($status);

        $edgeHeader = $this->request->headers->get(Header::X_EDGE_ROUTE_DETAILS);

        // Adding routeName and productName header used by developer-console
        // Checking the X routes using product key to block X request also
        // And filtering S2S routes so that no cards data will flow to developer-console
        if (empty($edgeHeader) === false &&
            $edgeHeader === 'true' &&
            $this->ba->getRequestOriginProduct() !== Product::BANKING &&
            in_array($route, route::S2S_PAYMENT_ROUTES, true) === false)
        {
            $response->header(Header::X_ROUTE_NAME, $route);
        }

        $this->stopBrowserCaching($response);

        $this->setSameOriginInHeaders($response, $route);

        $this->setRequestIdInHeaders($response, $debug);

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
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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
        $callbackRoutes = [
            'payment_create',
            'payment_create_fees',
            'payment_create_checkout',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_redirect_callback',
            'payment_callback_get',
            'payment_callback_post',
            'payment_redirect_to_authenticate_get',
            'payment_redirect_to_authenticate_post',
            'gateway_payment_callback_getsimpl_post',
        ];

        return (in_array($route, $callbackRoutes));
    }

    /**
     * These routes are the one which are used by checkout
     * for payment creation
     **/
    protected function isCheckoutCallbackRoute($route)
    {
        $callbackRoutes = [
            'payment_create_fees',
            'payment_create_checkout',
            'payment_callback_get',
            'payment_callback_post',
            'payment_callback_with_key_get',
            'payment_callback_with_key_post',
            'payment_redirect_callback',
            'payment_redirect_to_authorize_get',
            'payment_redirect_to_authenticate_get',
            'payment_redirect_to_authenticate_post',
            'gateway_payment_callback_getsimpl_post',
            'payment_mandate_hq_redirect_authenticate',
        ];

        return (in_array($route, $callbackRoutes));
    }

    protected function isCheckoutRoute($route)
    {
        $checkoutRoute = ['checkout'];

        return (in_array($route, $checkoutRoute));
    }

    protected function isJsonpRoute($route)
    {
        $jsonpRoutes = [
            'merchant_checkout_preferences',
            'merchant_methods',
            'merchant_public_get_banks',
            'payment_cancel',
            'payment_create_jsonp',
            'payment_get_status',
            'payment_get_flows',
            'payment_get_iin_details',
        ];

        return (in_array($route, $jsonpRoutes));
    }

    protected function setAccessControlAllowOriginStarOnSpecificRoutes($route, $response)
    {
        $routes = [
            'payment_cancel',
            'payment_create_ajax',
            'payment_otp_submit',
            'payment_otp_resend',
            'payment_otp_resend_json',
            'payment_topup_ajax',
            'merchant_methods_downtime',
            'customer_create_token_public',
            'refund_fetch_for_customer',
            'refunds_fetch_for_customer',
            'get_merchant_partner_status',
            'payment_get_status',
            'fund_account_create_public',
            'payment_validate_account',
            'payment_page_create_order',
            'payment_page_create_order_option',
            'payment_page_hosted_button_details',
            'payment_page_hosted_button_preferences',
            'hosted_subscription_button_details',
            'payment_button_create_order',
            'payment_button_create_order_option',
            'payment_button_hosted_preferences',
            'payment_button_hosted_button_details',
            'subscription_button_hosted_button_details',
            'payment_links_demo',
            'payment_links_demo_cors',
            'subscription_button_create_subscription',
            'subscription_button_create_subscription_v2',
            'freshdesk_create_ticket',
            'freshdesk_otp_send',
            'freshdesk_fetch_tickets',
            'freshdesk_raise_grievance',
            'freshdesk_account_recovery_create_ticket',
            'splitz_preflight',
            'splitz_preflight_bulk_evaluate',
            'splitz_evaluate',
            'splitz_evaluate_bulk',
            'customer_flagging_post_grievance',
            'customer_flagging_post_grievance_options',
            'customer_flagging_entity_details',
            'virtual_account_order_create',
            'salesforce_event_website',
            'salesforce_event_website_cors',
            'partner_kyc_approve_reject',
            'partner_kyc_approve_reject_cors',
            'merchant_tnc_details',
            'store_hosted_page_data',
            'store_hosted_page_data_options',
            'store_create_order',
            'store_create_order_options',
        ];

        if (in_array($route, $routes, true) === true)
        {
            //
            // These routes are being hit from razorpay.js which is being called
            // not from our own domain but someone else's. We need to allow for that
            // otherwise these routes will not work there. Read further on CORS
            // to understand better.
            //
            $response->headers->set(Header::ACCESS_CONTROL_ALLOW_ORIGIN, '*');
            return;
        }

        $supportPageRoutes = [
            'otp_post', // Support page is using otp_post route for sending otp
            'app_fetch_payments',
            'support_page_otp_verify',
        ];

        // Enabling this route to support send otp on support page https://razorpay.com/support/
        if (in_array($route, $supportPageRoutes, true) === true) {
            $response->headers->set(
                Header::ACCESS_CONTROL_ALLOW_ORIGIN,
                $this->app['config']->get('app.razorpay_support_page_url')
            );
        }

        // temporarily adding these routes due to issue with cardless emi s2s flow
        $tempRoutes = [
            'otp_verify',
            'otp_post',
        ];

        $merchant = $this->app['basicauth']->getMerchant();

        if ((in_array($route, $tempRoutes, true) === true) and
            ($merchant !== null) and
            (($merchant->isFeatureEnabled(Feature\Constants::S2S)) or
            ($merchant->isfeatureEnabled(Feature\Constants::ALLOW_S2S_APPS))))
        {
            $response->headers->set(Header::ACCESS_CONTROL_ALLOW_ORIGIN, '*');
        }
    }

    protected function setAccessControlAllowCredentialsTrueOnSpecificRoutes($route, $response): void
    {
        $routes = [
            'app_fetch_payments',
            'support_page_otp_verify',
        ];

        if (in_array($route, $routes, true) === true)
        {
            $response->headers->set(Header::ACCESS_CONTROL_ALLOW_CREDENTIALS, 'true');
        }
    }

    protected function setAccessControlAllowHeadersStarOnSpecificRoutes($route, $response): void
    {
        $routes = [
            'app_fetch_payments',
            'support_page_otp_verify',
        ];

        if (in_array($route, $routes, true) === true)
        {
            $response->headers->set(Header::ACCESS_CONTROL_ALLOW_HEADER, '*');
        }
    }

    protected function setRequestIdInHeaders(JsonResponse $response, bool $debug)
    {
        if (($debug === false) and
            ($this->app->environment(Environment::PRODUCTION) === true))
        {
            return;
        }

        $requestId = $this->request->getId();

        $response->headers->set(Header::REQUEST_ID, $requestId);
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
        $routes = ['checkout'];

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
        return $this->route->getCurrentRouteName();
    }

    /**
     * Returns true if given route is expected to render a view(always)
     * @param  string $route
     * @return bool
     */
    protected function isViewRoute(string $route = null): bool
    {
        return in_array($route, Route::$publicView, true);
    }

    /**
     * Generates and renders a fallback minimal error view
     * @param  array $data
     * @return \Illuminate\Http\Response
     */
    protected function generateDefaultErrorView(array $data)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = \View::make('public.error', ['data' => $data]);

        return \Response::make($response);
    }

    public function unauthorizedOauthAccessToRazorpayX()
    {
        return $this->generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_ACCESS_TO_RAZORPAYX_RESOURCE);
    }
}
