<?php

namespace App\Constants;

use App\Http\ApiUrl;

class Tracing
{
    // constants related to distributed tracing setup
    const SERVICE_NAME_IN_JAEGER = 'dashboard';
    const SPAN_KIND              = 'span.kind';
    const SERVER                 = 'server';
    const CLIENT                 = 'client';
    const INTERNAL               = 'internal';
    const QUERY                  = 'query';
    const ATTRIBUTES             = 'attributes';
    const HTTP                   = "http";
    const URL                    = 'url';
    const NAME                   = 'name';
    const ROUTE_PARAMS           = 'route.params.';
    const STATUS_CODE            = 'status_code';
    const ID_LENGTH              = 14;

    // all routes which are to be excluded from distributed tracing
    public static function getRoutesToExclude(): array
    {
        return [];
    }

    // all routes which are to be included from distributed tracing
    public static function getRoutesToInclude(): array
    {
        $routesToInclude = array_merge([
            'user_details',
            'user_mobile_details',
            'get_user_details',
            'user_signin',
            'admin_catchall',
            'merchant',
            'signin',
            'admin',
            'user_salesforce_event',
            'graph_request',
            'user_signin_otp',
            'user_signin_otp_verify',
            'user_signin_otp_2fa',
            'user_verify_user_otp',
            'user_verify_user_otp_verify',
            'user_register',
            'user_register_otp',
            'user_register_otp_verify',
            'user_oauth_signin',
            'user_oauth_register',
            'user_2fa_otp_verify',
            'user_2fa',
            'user_2fa_contact',
            'user_set_password',
            'user_2fa_otp_resned',
            'user_session',
            'user_identity',
            'user_pre_signup',
            'merchant_tags',
            'merchants_switch',
            'send_otp_salesforce_user',
            'verify_otp_salesforce_user',
            ]
        );

        return $routesToInclude;
    }

    // all routes which are to be included from distributed tracing
    public static function getOauthRoutesToInclude(): array
    {
        $oauthRoutesToInclude = array_merge([
                'graph_oauth',
                'oauth_user_pre_signup',
                'oauth_user_verify_email',
                'oauth_user_logout',
                'oauth_user_details',
                'oauth_merchant_experiments',
                'oauth_merchant_features',
                'oauth_merchant_details',
                'oauth_merchant',
                'oauth_get_org',
                'oauth_user',
                'oauth_user_signin_otp',
                'oauth_user_oauth_signin',
                'oauth_user_signin_otp_verify',
                'oauth_user_signin',
                'oauth_user_2fa_otp_resned',
                'oauth_user_verify_user_otp',
                'oauth_user_verify_user_otp_verify',
                'oauth_post_setup_2fa_verify_otp',
                'oauth_post_otp_login_2fa_password',
            ]
        );

        return $oauthRoutesToInclude;
    }

    public static function getServiceName($app): string
    {
        $app_mode = $app['config']->get('jaeger.app_mode');

        if($app_mode){
            return self::SERVICE_NAME_IN_JAEGER . '-' . $app_mode;
        }
        else{
            return self::SERVICE_NAME_IN_JAEGER;
        }
    }

    public static function getBasicSpanAttributes($app): array
    {
        $attrs = ['service.version' => $app['config']->get('jaeger.tag_service_version')]??"1.0";

        $attrs['product'] = ApiUrl::isBankingOriginRequest() ? "banking" : "primary";

        if (isset($app['request']))
        {
            $attrs['task_id'] = app('request')->requestId ?? "123";
        }

        $app_env = $app['config']->get('jaeger.tag_app_env');
        if($app_env){
            $attrs['app_env'] = $app_env;
        }

        $app_mode = $app['config']->get('jaeger.app_mode');
        if($app_mode){
            $attrs['app_mode'] = $app_mode;
        }

        return $attrs;
    }

    public static function shouldTraceRoute($route): bool
    {

        if (app('request.ctx')->isOauthRequest() === true &&
            in_array($route->getName(), self::getOauthRoutesToInclude()))
        {
            return true;
        }

        if(!(in_array($route->getName(), self::getRoutesToInclude())) or
            in_array($route->getName(), self::getRoutesToExclude()))
        {
            return false;
        }

        return true;
    }

    public static function isEnabled($app): bool
    {
        if ($app['config']->get('jaeger.enabled') === false)
        {
            return false;
        }

        return true;
    }

    public static function maskQueryString($queryString): string
    {
        $queryParamsToRedact = ['email', 'contact', 'customer_email', 'customer_contact', 'contact_ps'];
        parse_str($queryString, $queryParams);

        foreach ($queryParamsToRedact as $param)
        {
            if(array_key_exists($param, $queryParams))
            {
                $queryParams[$param] = '***';
            }
        }

        return urldecode(http_build_query($queryParams));
    }

    public static function maskUrl($url): string
    {
        $parsed = parse_url($url);

        if (isset($parsed['query'])) {
            $query = $parsed['query'];
            $parsed['query'] = self::maskQueryString($query);
        }

        return http_build_url($parsed);
    }
}
