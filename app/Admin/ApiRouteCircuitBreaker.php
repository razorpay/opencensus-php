<?php

namespace App\Admin;

use Config;
use App\Trace\TraceCode;
use App\Metrics\Constants;
use App\Http\RouteTeamMap;
use Symfony\Component\Routing\Route;
use Ackintosh\Ganesha as CircuitBreaker;
use App\Metrics\Constants as MetricsConstants;

class ApiRouteCircuitBreaker
{
    protected $circuitBreaker;

    protected $routeMethod;
    protected $routePath;

    protected $matchedRouteName;
    protected $matchedPathPattern;

    const API_ROUTE_DETAILS_CACHE_KEY          = 'api_route_details';
    const API_ROUTE_DETAILS_CACHE_TTL          =  60*24*7;  // 7 days

    const TIME_WINDOW                          = 'time_window';
    const FAILURE_RATE_THRESHOLD               = 'failure_rate_threshold';
    const MINIMUM_REQUESTS                     = 'minimum_requests';
    const INTERVAL_TO_HALF_OPEN                = 'interval_to_half_open';

    const BREAK_CIRCUIT                        = 'break_circuit';
    const ROUTE_NAME                           = 'route_name';
    const METHOD                               = 'method';
    const PATH_PATTERN                         = 'path_pattern';
    const PATH                                 = 'path';
    const OLD_ROUTE_NAME                       = 'old_route_name';
    const NEW_ROUTE_NAME                       = 'new_route_name';
    const OLD_PATH_PATTERN                     = 'old_path_pattern';
    const NEW_PATH_PATTERN                     = 'new_path_pattern';

    const SCHEME                               = 'scheme';
    const HOST                                 = 'host';
    const PORT                                 = 'port';
    const TCP                                  = 'tcp';

    const CIRCUIT_STATE                        = 'circuit_state';
    const CIRCUIT_OPEN                         = 'open';
    const CIRCUIT_CLOSE                        = 'close';

    const PREG_REPLACE_REGEX_FOR_PATH_MATCH    = '/\{(\w+?)\?\}/';
    const PREG_REPLACE_WITH_FOR_PATH_MATCH     = '{$1}';

    protected $app;

    protected $cache;

    protected $circuitState;

    protected array $teamLabels = [];

    protected $options = [
        // The interval in time (seconds) that evaluate the thresholds.
        self::TIME_WINDOW            => 60,

        // The failure rate threshold in percentage that changes CircuitBreaker's state to `OPEN`.
        self::FAILURE_RATE_THRESHOLD => 80,

        // The minimum number of requests to detect failures.
        // Even if `failureRateThreshold` exceeds the threshold,
        // CircuitBreaker remains in `CLOSED` if `minimumRequests` is below this threshold.
        self::MINIMUM_REQUESTS       => 20,

        // The interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`.
        self::INTERVAL_TO_HALF_OPEN  => 5,
    ];

    protected $excludedRoutesFailureThresholdAndMinimumRequests = [

//        Example for route and their threshold mapping
//        'merchant' => [
//            self::FAILURE_RATE_THRESHOLD => 80,
//            self::MINIMUM_REQUESTS       => 20,
//        ]
    ];

    function __construct($path, $method, $currentRouteName, $options = [])
    {
        $this->app            = \App::getFacadeRoot();

        $this->routeMethod    = strtolower($method);

        $this->routePath      = strtok($path, '?');

        $this->cache          = $this->app['cache'];

        $this->circuitBreaker = $this->getCircuitBreaker($currentRouteName);

        $this->options        = array_merge($options, $this->options);

        // default state is close
        $this->circuitState = self::CIRCUIT_CLOSE;

        $this->matchPath();
    }

    /**
     * @return array
     */
    public function getTeamLabels(): array
    {
        return $this->teamLabels;
    }

    /**
     * @return void
     */
    private function setTeamLabels(): void
    {
        $teamByRoute = RouteTeamMap::getTeamNamesForRoute($this->matchedRouteName);

        $tagByTeam = RouteTeamMap::getTeamSlackTag($teamByRoute);

        $this->teamLabels =  [
            Constants::LABEL_RZP_TEAM       => $teamByRoute,
            Constants::LABEL_RZP_TEAM_TAG   => $tagByTeam,
        ];
    }
  /**
   * @throws \Exception
   */
    public function validateRouteCircuitIsOpen($path, $method)
    {
        $breakCircuit = false;

        if ((is_null($this->matchedRouteName) === false) and
            ($this->circuitBreaker->isAvailable($this->matchedRouteName) === false))
        {
            $this->circuitState = self::CIRCUIT_OPEN;

            $breakCircuit = true;
        }

        $this->app['metrics']->count(
            MetricsConstants::API_CIRCUIT_BREAKER_STATE_COUNT,
            MetricsConstants::EVENT_COUNT_ONE, [
                MetricsConstants::CIRCUIT_STATE             => $this->circuitState,
                MetricsConstants::LABEL_HTTP_REQUESTS_ROUTE => $this->matchedRouteName ?? MetricsConstants::UNKNOWN_ROUTE,

            ]);

        $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_DECISION, [
            self::BREAK_CIRCUIT => $breakCircuit,
            self::ROUTE_NAME    => $this->matchedRouteName,
            self::METHOD        => $this->routeMethod,
            self::PATH_PATTERN  => $this->matchedPathPattern,
            self::PATH          => $this->routePath,
        ]);

        $is_api_circuit_breaker_enabled = false;

        $is_api_circuit_breaker_enabled_val = $this->app['config']->get('app.is_api_circuit_breaker_enabled') ?? false;

        if (((is_string($is_api_circuit_breaker_enabled_val) === true) and
            ($is_api_circuit_breaker_enabled_val === 'true')) or
            ($is_api_circuit_breaker_enabled_val === true))
        {
            $is_api_circuit_breaker_enabled = true;
        }

        if (($breakCircuit === true) and
            ($is_api_circuit_breaker_enabled === true))
        {
            throw new \Exception('Service Unavailable : 503');
        }
    }

    public function success()
    {
        if (is_null($this->matchedRouteName) === false)
        {
            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_MARK_SUCCESS, [
                self::ROUTE_NAME    => $this->matchedRouteName,
                self::METHOD        => $this->routeMethod,
                self::PATH_PATTERN  => $this->matchedPathPattern,
                self::PATH          => $this->routePath,
                self::CIRCUIT_STATE => $this->circuitState,
            ]);

            $this->circuitBreaker->success($this->matchedRouteName);
        }
        else
        {
            $this->app['trace']->error(TraceCode::API_CIRCUIT_BREAKER_MARK_SUCCESS_UNKNOWN_ROUTE, [
                self::METHOD        => $this->routeMethod,
                self::PATH          => $this->routePath,
            ]);
        }

        $this->app['metrics']->count(
            MetricsConstants::API_CIRCUIT_BREAKER_REQUEST_RESULT_COUNT,
            MetricsConstants::EVENT_COUNT_ONE, [
                MetricsConstants::CIRCUIT_STATE              => $this->circuitState,
                MetricsConstants::REQUEST_RESULT             => MetricsConstants::REQUEST_SUCCESS,
                MetricsConstants::LABEL_HTTP_REQUESTS_ROUTE  => $this->matchedRouteName ?? MetricsConstants::UNKNOWN_ROUTE,
            ] + $this->getTeamLabels());
    }

    public function failure($traceData = [])
    {
        if (is_null($this->matchedRouteName) === false)
        {
            $this->circuitBreaker->failure($this->matchedRouteName);

            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_MARK_FAILURE, array_merge($traceData, [
                self::ROUTE_NAME    => $this->matchedRouteName,
                self::METHOD        => $this->routeMethod,
                self::PATH_PATTERN  => $this->matchedPathPattern,
                self::PATH          => $this->routePath,
                self::CIRCUIT_STATE => $this->circuitState,
            ]));
        }
        else
        {
            $this->app['trace']->error(TraceCode::API_CIRCUIT_BREAKER_MARK_FAILURE_UNKNOWN_ROUTE, array_merge($traceData, [
                self::METHOD        => $this->routeMethod,
                self::PATH          => $this->routePath,
            ]));
        }

        $this->app['metrics']->count(
            MetricsConstants::API_CIRCUIT_BREAKER_REQUEST_RESULT_COUNT,
            MetricsConstants::EVENT_COUNT_ONE, [
                MetricsConstants::CIRCUIT_STATE              => $this->circuitState,
                MetricsConstants::REQUEST_RESULT             => MetricsConstants::REQUEST_FAILURE,
                MetricsConstants::LABEL_HTTP_REQUESTS_ROUTE  => $this->matchedRouteName ?? MetricsConstants::UNKNOWN_ROUTE,
            ] + $this->getTeamLabels());
    }

    public function saveApiRouteDetails($apiRouteName, $apiPathPattern)
    {
        if ((empty($apiRouteName) === true) or
            (empty($apiPathPattern) === true))
        {
            $this->app['trace']->error(TraceCode::API_CIRCUIT_BREAKER_ROUTE_DETAIL_HEADERS_MISSING, [
                self::METHOD        => $this->routeMethod,
                self::PATH          => $this->routePath,
            ]);

            return;
        }

        $apiRoutes = $this->cache->get(self::API_ROUTE_DETAILS_CACHE_KEY) ?? [];

        if ($this->doesRouteDetailNotExist($apiRoutes, $apiRouteName))
        {
            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_ADDING_ROUTE, [
                self::ROUTE_NAME   => $apiRouteName,
                self::METHOD       => $this->routeMethod,
                self::PATH_PATTERN => $apiPathPattern,
                self::PATH         => $this->routePath,
            ]);

            $this->updateApiRouteDetails($apiRoutes, $apiRouteName, $apiPathPattern);
        }
        else if ($this->isPathPatternUpdated($apiRoutes, $apiRouteName, $apiPathPattern))
        {
            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_UPDATING_PATH_PATTERN, [
                self::ROUTE_NAME        => $apiRouteName,
                self::METHOD            => $this->routeMethod,
                self::OLD_PATH_PATTERN  => $apiRoutes[$this->routeMethod][$apiRouteName],
                self::NEW_PATH_PATTERN  => $apiPathPattern,
                self::PATH              => $this->routePath,
            ]);

            $this->updateApiRouteDetails($apiRoutes, $apiRouteName, $apiPathPattern);
        }
        else if ($this->isRouteNameUpdated($apiRouteName, $apiPathPattern))
        {
            // unset old details
            unset($apiRoutes[$this->routeMethod][$this->matchedRouteName]);

            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_UPDATING_ROUTE_NAME, [
                self::OLD_ROUTE_NAME    => $this->matchedRouteName,
                self::NEW_ROUTE_NAME    => $apiRouteName,
                self::METHOD            => $this->routeMethod,
                self::PATH_PATTERN      => $apiPathPattern,
                self::PATH              => $this->routePath,
            ]);

            $this->updateApiRouteDetails($apiRoutes, $apiRouteName, $apiPathPattern);
        }

        // in case of matched route name is null or not same as in api
        $this->matchedRouteName = $apiRouteName;

        // since matchedRouteName is updated, update the team labels as well
        $this->setTeamLabels();
    }

    protected function doesRouteDetailNotExist($apiRoutes, $apiRouteName)
    {
        if ((is_null($this->matchedRouteName) === true) and
            (empty($apiRoutes[$this->routeMethod][$apiRouteName]) === true))
        {
            return true;
        }

        return false;
    }

    protected function isPathPatternUpdated($apiRoutes, $apiRouteName, $apiPathPattern)
    {
        if ((is_null($this->matchedRouteName) === true) and
            (empty($apiRoutes[$this->routeMethod][$apiRouteName]) === false) and
            ($apiRoutes[$this->routeMethod][$apiRouteName] !== $apiPathPattern))
        {
            return true;
        }

        return false;
    }

    protected function isRouteNameUpdated($apiRouteName, $apiPathPattern)
    {
        if ((is_null($this->matchedRouteName) === false) and
            ($this->matchedRouteName !== $apiRouteName) and
            ($this->matchedPathPattern === $apiPathPattern))
        {
            return true;
        }

        return false;
    }

    protected function updateApiRouteDetails($apiRoutes, $apiRouteName, $apiPathPattern)
    {
        // add route or update path pattern
        $apiRoutes[$this->routeMethod][$apiRouteName] = $apiPathPattern;

        $this->cache->put(self::API_ROUTE_DETAILS_CACHE_KEY, $apiRoutes, self::API_ROUTE_DETAILS_CACHE_TTL);
    }

    protected function matchPath()
    {
        $apiRoutes = $this->cache->get(self::API_ROUTE_DETAILS_CACHE_KEY);

        $routes = $apiRoutes[$this->routeMethod] ?? [];

        if ($this->matchPathPatternWithRoutes($routes))
        {
            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_PATH_MATCHED, [
                self::ROUTE_NAME    => $this->matchedRouteName,
                self::PATH_PATTERN  => $this->matchedPathPattern,
                self::METHOD        => $this->routeMethod,
                self::PATH          => $this->routePath,
            ]);
        }
        else
        {
            $this->app['trace']->info(TraceCode::API_CIRCUIT_BREAKER_PATH_NOT_MATCHED, [
                self::METHOD        => $this->routeMethod,
                self::PATH          => $this->routePath,
            ]);
        }
    }

    public function getApiPathName()
    {
       return $this->matchedRouteName;
    }

    protected function matchPathPatternWithRoutes($routes)
    {
        foreach ($routes as $routeName => $pathPattern)
        {
            if ($this->matchPathPattern($pathPattern, $this->routePath) === true)
            {
                $this->matchedPathPattern = $pathPattern;

                $this->matchedRouteName   = $routeName;

                // since matchedRouteName is updated, update the team labels as well
                $this->setTeamLabels();

                return true;
            }
        }

        $this->matchedPathPattern = null;

        $this->matchedRouteName   = null;

        // since matchedRouteName is updated, update the team labels as well
        $this->setTeamLabels();

        return false;
    }

    /**
     * This function uses same logic as larval uses for path pattern match
     * @param $pathPattern
     * @param $path
     * @return bool
     */
    protected function matchPathPattern($pathPattern, $path)
    {
        $pathPattern = trim($pathPattern, '/');

        $optionals = $this->extractOptionalParameters($pathPattern);

        $whereAs = [];

        if (strpos($pathPattern, '{path?}') !== false)
        {
            $whereAs = ['path' => '.*'];
        }

        $pathPattern = preg_replace(self::PREG_REPLACE_REGEX_FOR_PATH_MATCH, self::PREG_REPLACE_WITH_FOR_PATH_MATCH, $pathPattern); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval

        $route = new Route($pathPattern, $optionals, $whereAs, ['utf8' => true]);

        $path = '/' . $path;

        return preg_match($route->compile()->getRegex(), rawurldecode($path)) === 1;
    }

    /**
     * @param $pathPattern
     * @return array
     */
    protected function extractOptionalParameters($pathPattern)
    {
        preg_match_all('/\{(\w+?)\?\}/', $pathPattern, $matches);

        return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
    }

    protected function getFailureRateThresholdForCurrentRoute($currentRouteName)
    {
        if (($currentRouteName !== null) and
            (isset($this->excludedRoutesFailureThresholdAndMinimumRequests[$currentRouteName])))
        {
            return $this->excludedRoutesFailureThresholdAndMinimumRequests[$currentRouteName][self::FAILURE_RATE_THRESHOLD];
        }

        return $this->options[self::FAILURE_RATE_THRESHOLD];
    }

    protected function getMinimumRequestForCurrentRoute($currentRouteName)
    {
        if (($currentRouteName !== null) and
            (isset($this->excludedRoutesFailureThresholdAndMinimumRequests[$currentRouteName])))
        {
            return $this->excludedRoutesFailureThresholdAndMinimumRequests[$currentRouteName][self::MINIMUM_REQUESTS];
        }

        return $this->options[self::MINIMUM_REQUESTS];
    }

    protected function getCircuitBreaker($currentRouteName)
    {
        $adapter = new CircuitBreaker\Storage\Adapter\RedisStore($this->app['redis']->client());

        $failureRateThreshold = $this->getFailureRateThresholdForCurrentRoute($currentRouteName);
        $minimumRequest       = $this->getMinimumRequestForCurrentRoute($currentRouteName);

        return CircuitBreaker\Builder::withRateStrategy()
            // The interval in time (seconds) that evaluate the thresholds.
            ->timeWindow($this->options[self::TIME_WINDOW])
            // The failure rate threshold in percentage that changes CircuitBreaker's state to `OPEN`.
            ->failureRateThreshold($failureRateThreshold)
            // The minimum number of requests to detect failures.
            // Even if `failureRateThreshold` exceeds the threshold,
            // CircuitBreaker remains in `CLOSED` if `minimumRequests` is below this threshold.
            ->minimumRequests($minimumRequest)
            // The interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`.
            ->intervalToHalfOpen($this->options[self::INTERVAL_TO_HALF_OPEN])
            // The storage adapter instance to store various statistics to detect failures.
            ->adapter( new CircuitBreaker\Storage\Adapter\Redis($adapter))
            ->build();
    }
}
