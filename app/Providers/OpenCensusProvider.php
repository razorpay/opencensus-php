<?php

// reference: https://opencensus.io/api/php
// https://github.com/nenad/opencensus-php/tree/master/src

namespace RZP\Providers;

use RZP\Constants\Tracing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Integrations\Laravel;
use OpenCensus\Trace\Integrations\Redis;
use OpenCensus\Trace\Integrations\Curl;
use OpenCensus\Trace\Propagator\JaegerPropagator;

class OpenCensusProvider extends ServiceProvider
{
    public function boot()
    {
        if (Tracing::isEnabled($this->app) === false)
        {
            return;
        }

        Route::matched(function($event) {

            $currentRoute = $event->route;

            if (Tracing::shouldTraceRoute($currentRoute) === false)
            {
                return;
            }

            // Load all useful extensions
            // PDO is loaded while connecting in MySqlConnector.php

            Redis::load();
            Curl::load();

            $spanOptions = $this->getSpanOptions($currentRoute);

            $headers = $_SERVER;

            if (isset($headers['QUERY_STRING'])) {
                $headers['QUERY_STRING'] = Tracing::maskQueryString($headers['QUERY_STRING']);
            }

            if (isset($headers['REQUEST_URI'])) {
                $headers['REQUEST_URI'] = Tracing::maskUrl($headers['REQUEST_URI']);
            }

            $propagator = new JaegerPropagator();
            $tracerOptions = [
                'headers'           => $headers,
                'propagator'        => $propagator,
                'root_span_options' => $spanOptions
            ];

            $serviceName = Tracing::getServiceName($this->app);

            $jaegerExporterOptions = ['host' =>  $this->app['config']->get('applications.jaeger.host'),
                             'port' =>  $this->app['config']->get('applications.jaeger.port')
                            ];

            $exporter = new JaegerExporter($serviceName, $jaegerExporterOptions);
            Tracer::start($exporter, $tracerOptions);
        });
    }

    private function getSpanOptions($route)
    {
        $parametrizedRoute = $route->uri();

        $attrs = Tracing::getBasicSpanAttributes($this->app);
        $attrs['span.kind'] = 'server';
        $attrs['http.url'] = URL::current();


        $spanOptions = ['name' => $parametrizedRoute, 'attributes' => $attrs];

        /*
        route parameters that need to go as a main attribute name,
        to be able to do tag search/aggregate go here.

        Following map allows renaming of them, to standardize across routes.
        */

        $routeParamTagMap = ['merchantId'       => 'merchants_id',
                                'merchant_id'   => 'merchants_id',
                                'mid'           => 'merchants_id',
                                'paymentId'     => 'payments_id',
                                'payment_id'    => 'payments_id',
                                'gateway'       => 'gateway',
                                'bank'          => 'bank',
                                'channel'       => 'channel',
                                'customer_id'   => 'customers_id'
                            ];

        // extract route parameters and add them as span attributes
        $routeParamPrefix = 'http.route.params.';
        if($route->hasParameters())
        {
            foreach ($route->parameters as $key => $value)
            {
                $spanOptions['attributes'][$routeParamPrefix . $key] = $value;

                if (array_key_exists($key, $routeParamTagMap))
                {
                    $attrName = $routeParamTagMap[$key];
                    $spanOptions['attributes'][$attrName] = $value;
                }
            }
        }

        // if route has pattern "/<resourceName>/{id}" in it, add <resourceName>_id as span attribute
        // handles /payments/{id}, /customers/{id}, /merchants/{id}, /refunds/{id} etc

        // if there are multiple {id}'s in route, take only the first

        if (strpos($parametrizedRoute, '{id}') !== false)
        {
            $routeParts = explode('/', $parametrizedRoute);
            $resourceIndex = array_search('{id}', $routeParts)-1;
            if ($resourceIndex >= 0)
            {
                $resourceName = $routeParts[$resourceIndex];
                $spanOptions['attributes'][$resourceName . '_id'] = $route->parameters['id'];
            }
        }

        return $spanOptions;
    }
}
