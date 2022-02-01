<?php

namespace App\Constants;

class Tracing
{
    // constants related to distributed tracing setup
    const SERVICE_NAME_IN_JAEGER = 'dashboard';
    const SPAN_KIND              = 'span.kind';
    const SERVER                 = 'server';
    const CLIENT                 = 'client';
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
            'admin'
            ]
        );

        return $routesToInclude;
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
}
