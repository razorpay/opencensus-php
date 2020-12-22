<?php

namespace RZP\Constants;

use RZP\Http\Route;

class Tracing
{
    // constants related to distributed tracing setup
    const SERVICE_NAME_IN_JAEGER      =   'api';

    // all routes which are to be excluded from distributed tracing
    public static function getRoutesToExclude(): array
    {
        $allCronRoutes =  Route::$internalApps['cron'];

        // for now it's only cron routes. add anything else here
        $routesToExclude = $allCronRoutes ;
        return $routesToExclude;
    }

    // all routes which are to be included from distributed tracing
    public static function getRoutesToInclude(): array
    {
        $routesToInclude = array_merge(Route::$public, Route::$direct);

        return array_merge($routesToInclude, [
                'user_fetch',
                'capital_cards_service',
                'capital_cards_admin',
            ]);
    }

    public static function getServiceName($app): string
    {
        $app_mode = $app['config']->get('applications.jaeger.app_mode');

        if($app_mode){
            return self::SERVICE_NAME_IN_JAEGER . '-' . $app_mode;
        }
        else{
            return self::SERVICE_NAME_IN_JAEGER;
        }
    }

    public static function getBasicSpanAttributes($app): array
    {
        $attrs = ['service.version' => $app['config']->get('applications.jaeger.tag_service_version')];

        if(isset($app['rzp.mode'])){
            $attrs['rzp_mode'] = $app['rzp.mode'];
        }

        if (isset($app['request']))
        {
            $attrs['task_id'] = $app['request']->getTaskId();
        }

        $app_env = $app['config']->get('applications.jaeger.tag_app_env');
        if($app_env){
            $attrs['app_env'] = $app_env;
        }

        $app_mode = $app['config']->get('applications.jaeger.app_mode');
        if($app_mode){
            $attrs['app_mode'] = $app_mode;
        }

        return $attrs;
    }
}

