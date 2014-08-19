<?php

namespace Http;

final class Route
{
    /*
     | The order in which routes are defined is very important.
     | Whenever the order of routes is changed,
     | make sure to run the full test suite
     */

    protected static $apiRoutes = array(
        'transaction_create'        => ['post', 'transactions',                 'TransactionController@postCreateTransaction'],
        'transaction_jsonp'         => ['post', 'transactions/jsonp',           'TransactionController@getJSONP'],
        'transaction_callback'      => ['post', 'transactions/callback/{id}',   'TransactionController@postCallback'],
        'transaction_refund'        => ['post', 'transactions/{id}/refund',     'TransactionController@postRefund'],
        'transaction_capture'       => ['post', 'transactions/{id}/capture',    'TransactionController@postCapture'],
        'transaction_fetch_by_id'   => ['get',  'transactions/{id}',            'TransactionController@getTransaction'],
        'transaction_fetch_multiple'=> ['get',  'transactions/{param?}',        'TransactionController@getTransactions'],
        'merchant_create'           => ['post', 'merchants',                    'MerchantController@postCreateMerchant'],
        'merchant_fetch_keys'       => ['get',  'merchants/{id}/keys',          'MerchantController@getKeys'],
        'merchant_replace_key'      => ['put',  'merchants/{merchantId}/keys/{keyId}', 'MerchantController@putKeys'],
        'merchant_assign_pricing'   => ['post', 'merchants/{id}/pricing',       'MerchantController@postAssignPricingPlan'],
        'merchant_get_pricing'      => ['get',  'merchants/{id}/pricing',       'MerchantController@getPricingPlan'],
        'merchant_create_terminal'  => ['post', 'merchants/{id}/terminal',      'MerchantController@postCreateTerminal'],
        'merchant_get_terminal'     => ['get',  'merchants/{id}/terminal',      'MerchantController@getTerminals'],
        'merchant_activate'         => ['post', 'merchants/{id}/activate',      'MerchantController@postActivate'],
        'pricing_create_plan'       => ['post', 'pricing',                      'PricingController@postCreatePricingPlan'],
        'pricing_get_plans'         => ['get',  'pricing',                      'PricingController@getPricingPlans'],
        'pricing_get_merchant_plans'=> ['get',  'pricing/merchants',            'PricingController@getMerchantPricingPlans'],
        'pricing_get_gateway_plans' => ['get',  'pricing/gateways',             'PricingController@getGatewayPricingPlans'],
        'pricing_get_plan'          => ['get',  'pricing/{id}',                 'PricingController@getPricingPlan'],
        'pricing_get_plan_rule'     => ['get',  'pricing/{planId}/rule/{ruleId}', 'PricingController@getPricingPlanRule'],
        'pricing_add_plan_rule'     => ['post', 'pricing/{id}/rule',            'PricingController@postAddPricingPlanRule'],
        );

    public static $public = array(
        'transaction_create',
        'transaction_jsonp',
        'transaction_callback',
        );

    public static $private = array(
        'transaction_refund',
        'transaction_capture',
        'transaction_fetch_by_id',
        'transaction_fetch_multiple',
        );

    public static $internal = array(
        'merchant_create',
        'merchant_fetch_keys',
        'merchant_replace_key',
        'merchant_assign_pricing',
        'merchant_get_pricing',
        'merchant_create_terminal',
        'merchant_get_terminal',
        'merchant_activate',
        'pricing_create_plan',
        'pricing_get_plans',
        'pricing_get_merchant_plans',
        'pricing_get_gateway_plans',
        'pricing_add_plan_rule',
        'pricing_get_plan',
        'pricing_get_plan_rule',
        );

    protected static $router;

    public static function setRouter($router)
    {
        self::$router = $router;
    }

    public static function getDoNotLogURLs()
    {
        $doNotLogUrls = array(
            self::$apiRoutes['transaction_jsonp'][1]);

        return $doNotLogUrls;
    }

    public static function isJsonpRoute($route)
    {
        $jsonpRoute = array(
            self::$apiRoutes['transaction_jsonp'][1]);

        return in_array($route, $jsonpRoute);
    }

    protected static function addRoutes($type)
    {
        foreach (self::$$type as $routeName)
        {
            $routeInfo = self::$apiRoutes[$routeName];

            $method = $routeInfo[0];
            $uri = $routeInfo[1];
            $action = $routeInfo[2];

            $router = self::$router;

            $router->$method($uri, array('as' => $routeName, 'uses' => $action));
        }
    }

    public static function defineApiRoutes()
    {
        $router = self::$router;

        $router->group(array('prefix' => 'v1'), function() use ($router)
        {
            //
            // First define internal routes and then private and finally public
            // If by mistake a route is defined twice in say internal and public,
            // then it will go into internal app auth and will not expose the route.
            // This must not happen though.
            //
            $router->group(array('before' => 'auth.app'), function()
            {
                self::addRoutes('internal');
            });

            $router->group(array('before' => 'auth.private'), function()
            {
                self::addRoutes('private');
            });

            $router->group(array('before' => 'auth.public'), function()
            {
                self::addRoutes('public');
            });
        });

        $router->get('/', function()
        {
            $response['message'] = "Welcome to Razorpay API.";
            return Http\ApiResponse::json($response);
        });

        $router->any('{all}', function($uri)
        {
            return Http\ApiResponse::routeNotFound();
        })->where('all', '.*');
    }

    public static function getApiRoutes()
    {
        return self::$apiRoutes;
    }

    public static function getApiRoute($name)
    {
        return self::$apiRoutes[$name];
    }

    public static function callback($id)
    {
        $route = 'transaction_callback';

        $pos = strrpos($route, '/');

        $urlSegment = substr($urlSegment, 0, $pos);

        $urlSegment .= '/' . $txn->getPublicId();

        $scheme = \Request::getScheme().'://';
        $key = \BasicAuth::getPublicKey();
        $host = \Request::getHost();

        $callbackUrl = $scheme . $key . '@' . $host . '/' . $urlSegment;

        $callbackData['callbackUrl'] = $callbackUrl;
    }
}