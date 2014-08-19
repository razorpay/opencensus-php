<?php

namespace Http;

use Route;

final class URL
{
    public static $public = array(
        'transaction_create'        => ['post', 'transactions', 'TransactionController@postCreateTransaction'],
        'transaction_jsonp'         => ['post', 'transactions/jsonp', 'TransactionController@postCallback'],
        'transaction_callback'      => ['post', 'transactions/callback/{id}', 'TransactionController@getJSONP'],
        );

    public static $private = array(
        'transaction_refund'            => ['post', 'transactions/{id}/refund', 'TransactionController@postRefund'],
        'transaction_capture'           => ['post', 'transactions/{id}/capture', 'TransactionController@postCapture'],
        'transaction_fetch_by_id'       => ['get',  'transactions/{id}', 'TransactionController@getTransaction'],
        'transaction_fetch_multiple'    => ['get',  'transactions/{param?}', 'TransactionController@getTransactions'],
        );

    public static $internal = array(
        'merchant_create'           => ['post', 'merchants',                'MerchantController@postCreateMerchant'],
        'merchant_fetch_keys'       => ['get',  'merchants/{id}/keys',      'MerchantController@getKeys'],
        'merchant_replace_key'      => ['put',  'merchants/{merchantId}/keys/{keyId}', 'MerchantController@putKeys'],
        'merchant_assign_pricing'   => ['post', 'merchants/{id}/pricing',   'MerchantController@postAssignPricingPlan'],
        'merchant_get_pricing'      => ['get',  'merchants/{id}/pricing',   'MerchantController@getPricingPlan'],
        'merchant_create_terminal'  => ['post', 'merchants/{id}/terminal',  'MerchantController@postCreateTerminal'],
        'merchant_get_terminal'     => ['get',  'merchants/{id}/terminal',  'MerchantController@getTerminals'],
        'merchant_activate'         => ['post', 'merchants/{id}/activate',  'MerchantController@postActivate'],
        'pricing_create_plan'       => ['post', 'pricing',                  'PricingController@postCreatePricingPlan'],
        'pricing_get_plans'         => ['get',  'pricing',                  'PricingController@getPricingPlans'],
        'pricing_get_merchant_plans'=> ['get',  'pricing/merchants',        'PricingController@getMerchantPricingPlans'],
        'pricing_get_gateway_plans' => ['get',  'pricing/gateways',         'PricingController@getGatewayPricingPlans'],
        'pricing_add_plan_rule'     => ['post', 'pricing/{id}/rule',        'PricingController@postAddPricingPlanRule'],
        'pricing_get_by_id'         => ['get',  'pricing/{id}',             'PricingController@getPricingPlan'],
        'pricing_get_plan_rule'     => ['get',  'pricing/{planId}/rule/{ruleId}', 'PricingController@getPricingPlanRule'],
        );

    public static function getDoNotLogURLs()
    {
        $doNotLogUrls = array(
            self::$public['transaction_jsonp'][1]);

        return $doNotLogUrls;
    }

    public static function isJsonpUrl($url)
    {
        $jsonpUrls = array(
            self::$public['transaction_jsonp'][1]);

        return in_array($url, $jsonpUrls);
    }

    public static function callback($id)
    {
        $urlSegment = self::TXN_CALLBACK_URL;

        $pos = strrpos($urlSegment, '/');

        $urlSegment = substr($urlSegment, 0, $pos);

        $urlSegment .= '/' . $txn->getPublicId();

        $scheme = \Request::getScheme().'://';
        $key = \BasicAuth::getPublicKey();
        $host = \Request::getHost();

        $callbackUrl = $scheme . $key . '@' . $host . '/' . $urlSegment;

        $callbackData['callbackUrl'] = $callbackUrl;
    }

    public static function buildUrl($segment, $arg = null, $auth = null)
    {
        if (isset($arg))
        {
            $pos = strrpos($urlSegment, '/');

            $urlSegment = substr($urlSegment, 0, $pos);

            $urlSegment .= '/' . $txn->getPublicId();


        }
    }

    public static function addRoutes($type)
    {
        foreach (self::$$type as $routeName => $routeInfo)
        {
            $method = $routeInfo[0];
            $url = $routeInfo[1];
            $action = $routeInfo[2];

            Route::$method($url, array('as' => $routeName, 'uses' => $action));
        }
    }
}