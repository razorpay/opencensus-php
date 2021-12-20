<?php

namespace App\Providers;

use Trace;
use App\Trace\TraceCode;
use App\Constants\Tracing;
use OpenCensus\Trace\Tracer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use OpenCensus\Trace\Integrations\Curl;
use Illuminate\Support\ServiceProvider;
use OpenCensus\Trace\Integrations\Redis;
use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\Propagator\JaegerPropagator;

class OpenCensusProvider extends ServiceProvider
{
    public function boot()
    {
        if (Tracing::isEnabled($this->app) === false)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_enabled' => false,
            ]);
            return;
        }

        Route::matched(function($event) {

            $currentRoute = $event->route;

            if (Tracing::shouldTraceRoute($currentRoute) === false)
            {
                Trace::info(TraceCode::JAEGER_INFO, [
                    'jaeger_app_route' => false,
                    'route_name'       => $currentRoute->getName(),
                ]);

                return;
            }

            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => true,
                'route_name'       => $currentRoute->getName(),
            ]);
            // Load all useful extensions
            // PDO is loaded while connecting in MySqlConnector.php
            Redis::load();
            Curl::load();

            $attrs                     = Tracing::getBasicSpanAttributes($this->app);
            $attrs[Tracing::SPAN_KIND] = Tracing::SERVER;

            $spanOptions = $this->getSpanOptions($currentRoute);

            $propagator    = new JaegerPropagator();
            $tracerOptions = [
                'propagator'        => $propagator,
                'root_span_options' => $spanOptions];

            $serviceName = Tracing::getServiceName($this->app);

            $jaegerExporterOptions = [
                'host' => $this->app['config']->get('jaeger.host'),
                'port' => $this->app['config']->get('jaeger.port')
            ];

            $exporter = new JaegerExporter($serviceName, $jaegerExporterOptions);
            Tracer::start($exporter, $tracerOptions);
        });
    }

    private function getSpanOptions($route)
    {
        $attrs = Tracing::getBasicSpanAttributes($this->app);

        $attrs[Tracing::SPAN_KIND] = Tracing::SERVER;

        $attrs[Tracing::HTTP . '.' . Tracing::URL] = URL::current();

        $parameterMap = Route::current()->parameters();

        $spanName = $this->getSpanName($attrs[Tracing::HTTP . '.' . Tracing::URL]);

        $spanOptions = [Tracing::NAME => $spanName, Tracing::ATTRIBUTES => $attrs];

        $routeParamPrefix = Tracing::HTTP . '.' . Tracing::ROUTE_PARAMS;

        if(empty($parameterMap) === false)
        {
            foreach ($parameterMap as $key => $value)
            {
                $spanOptions[Tracing::ATTRIBUTES][$routeParamPrefix . $key] = $value;
            }
        }

        $spanOptions[Tracing::ATTRIBUTES]['route_name'] = $route->getName();

        return $spanOptions;
    }

    private function getSpanName($url)
    {
        $urlInfo = parse_url($url);

        $spanName = $urlInfo['path'];

        $explodedPath = explode("/",$spanName);

        $strToReplaceInName = [];

        foreach ($explodedPath as $key => $value)
        {
            $splitArr = explode("_", $value);

            if (strlen($value) === Tracing::ID_LENGTH ||
                (sizeof($splitArr) === 2) && strlen($splitArr[1]) === Tracing::ID_LENGTH)
            {
                $strToReplaceInName [] = "{id}";
            }
            else
            {
                $strToReplaceInName [] = $value;
            }
        }

        if (empty($strToReplaceInName) === false)
        {
            $spanName = implode("/",$strToReplaceInName);
        }

        return $spanName;
    }

    public function register()
    {
    }
}
