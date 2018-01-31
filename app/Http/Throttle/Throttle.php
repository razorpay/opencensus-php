<?php

namespace RZP\Http\Throttle;

use App;
use Illuminate\Http\Request;
use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;

use RZP\Constants\Mode;
use RZP\Http\{Route, RequestHeader};
use RZP\Exception\ThrottleException;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Throttles requests to API
 *
 * Approach:
 * - For every incoming request we identify route, mode, auth and a identifier.
 *   - route:       Name of the route
 *   - mode:        Application mode (live|test)
 *   - identifier:  An user identifier (who is being throttled) - e.g. Merchant
 *                  key, Admin user email etc. This same gets used in
 *                  constructing final REDIS key which gets throttled(e.g.
 *                  identifier + ip address etc)
 *
 * - In REDIS we keep some dynamic configurations(explained below), we fetch
 *   that and prepare throttle parameters (key which gets throttled, and limits
 *   values (leak rate, duration , burst)).
 *
 * - We use leaky bucket implementation to finally throttle using above parameters.
 *
 * Settings in REDIS:
 *
 * - Global settings:
 *   - Keeps global settings for throttle.
 *
 *   {
 *       skip:                  0|1,
 *       <mode>:<auth>:l:lrv:   100,
 *       <mode>:<auth>:l:mbs:   30,
 *   }
 *
 * - Route level settings:
 *   - Keeps route level settings for throttle & block, e.g. route specific limits
 *   - Also for some routes and identifier combination can increase/decrease the
 *     limit and also block them.
 *
 *   {
 *       <mode>:b:                          1,
 *       <mode>:<identifier>:b:             1,
 *       <mode>:<auth>:l:lrv:               100,
 *       <mode>:<auth>:l:mbs:               30,
 *       <mode>:<auth>:<identifier>:l:lrv:  100,
 *       <mode>:<auth>:<identifier>:l:mbs:  30,
 *   }
 *
 * - Identifier level settings:
 *   - Keeps identifier level settings for throttle & block.
 *
 *   {
 *       b:                     1,
 *       <mode>:b:              1,
 *       <mode>:<auth>:b:       1,
 *   }
 *
 */
class Throttle
{
    private $config;
    private $appsConfig;
    private $trace;
    private $router;
    private $redis;

    public function __construct()
    {
        $app              = App::getFacadeRoot();
        $this->config     = $app['config']->get('throttle');
        $this->appsConfig = $app['config']->get('applications');
        $this->trace      = $app['trace'];
        $this->router     = $app['router'];
    }

    public function throttle(Request $request): array
    {
        // Usually in local or test ENV we skip basis local configuration
        if ($this->config['skip'] === true)
        {
            return [];
        }

        $route                          = $this->router->currentRouteName();
        list($mode, $auth, $identifier) = $this->getModeAuthAndIdentifier($request, $route);
        $settings                       = $this->getThrottleSettings($route, $identifier);

        // Throttling and blocking may be temporarily skipped via remote configuration
        if (($settings[0]['skip'] ?? '0') === '1')
        {
            return [];
        }

        return $this->throttleUsingLeakyBucket($request, $route, $mode, $auth, $identifier, $settings);
    }

    /**
     * Gets the 3 settings hash(global, for route & for identifier respectively)
     * from REDIS using pipeline command(1 network call).
     *
     * @param  string $route
     * @param  string $identifier
     *
     * @return array
     */
    private function getThrottleSettings(string $route, string $identifier): array
    {
        return $this->redisClient()->pipeline(
            function ($pipe) use ($route, $identifier)
            {
                $pipe->hgetall(Constant::GLOBAL_SETTINGS_KEY);
                $pipe->hgetall(Constant::ROUTE_SETTINGS_KEY_REFIX . $route);
                $pipe->hgetall(Constant::IDENTIFIER_SETTINGS_KEY_PREFIX . $identifier);
            });
    }

    /**
     * Constructs throttle key, and extracts throttle limits for current context
     * from settings obtained from REDIS and then calls leaky bucket throttle.
     *
     * @param  Request $request
     * @param  string  $route
     * @param  string  $mode
     * @param  string  $auth
     * @param  string  $identifier
     * @param  array   $settings
     *
     * @return array
     */
    private function throttleUsingLeakyBucket(
        Request $request,
        string $route,
        string $mode,
        string $auth,
        string $identifier,
        array $settings): array
    {
        // Considers IP address for public routes only in constructing throttle key
        $key           = "$identifier:$mode:$route" . ($auth === AuthType::PUBLIC_AUTH ? ':' . $request->ip() : '');
        // Throttle defaults to "leak at the rate of 3 per sec and allows max burst of 50"
        $leakRate      = $this->getThrottleLimits(Constant::LEAK_RATE_VALUE, 3, $mode, $auth, $identifier, $settings);
        $leakDuration  = $this->getThrottleLimits(Constant::LEAK_RATE_DURATION, 1000, $mode, $auth, $identifier, $settings);
        $maxBucketSize = $this->getThrottleLimits(Constant::MAX_BUCKET_SIZE, 50, $mode, $auth, $identifier, $settings);

        $allowed = 1;
        $limits  = [];

        try
        {
            $limiter  = new LeakyBucket\Redis($maxBucketSize, $leakRate, $leakDuration, $this->redisClient());
            $response = $limiter->attempt($key);
            $allowed  = array_shift($response);
            $limits   = $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        if ($allowed === 0)
        {
            throw new ThrottleException(null, $limits);
        }

        return $limits;
    }

    /**
     * Get throttle limit value for given key
     *
     * @param  string $key
     * @param  int    $default
     * @param  string $mode
     * @param  string $auth
     * @param  string $identifier
     * @param  array  $settings
     *
     * @return int
     */
    private function getThrottleLimits(
        string $key,
        int $default,
        string $mode,
        string $auth,
        string $identifier,
        array $settings): int
    {
        // Value for given route, mode, auth & identifier combination
        return $settings[1]["$mode:$auth:$identifier:limits:$key"] ??
        // Else value for given route, mode & auth combination
                $settings[1]["$mode:$auth:limits:$key"] ??
        // Else value for given mode & auth combination
                $settings[0]["$mode:$auth:limits:$key"] ??
        // Else the default value
                $default;
    }

    private function redisClient(): \Predis\Client
    {
        return $this->redis ?: ($this->redis = Redis::connection('throttle')->client());
    }

    /**
     * Gets mode, auth and identifier for current requests.
     *
     * @param  Request $request
     * @param  string  $route
     *
     * @return array
     */
    private function getModeAuthAndIdentifier(Request $request, string $route): array
    {
        // key can come in request input as key_id for public routes
        $key = $request->input('key_id') ?:
        // key can come as part of route parameters for callback URLS
                $this->router->current()->parameter('key') ?:
        // key for all other case comes as HTTP basic auth user name
                $request->getUser();
        $secret = $request->getPassword();
        $mode = substr($key, 4, 4);
        // Just for not getting broken elsewhere if someone sends incorrect key
        $mode = Mode::exists($mode) ? $mode : Mode::LIVE;
        // Key is actually just the part after rzp_{$mode}_
        $key  = substr($key, 9);

        if (in_array($route, Route::$internal, true) === true)
        {
            $auth       = AuthType::PRIVILEGE_AUTH;
            $identifier = $secret;
        }
        else if (in_array($route, Route::$admin, true) === true)
        {
            $auth       = AuthType::ADMIN_AUTH;
            $identifier = $request->headers(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
        }
        else if ((in_array($route, Route::$private, true) === true) and
            ($this->isDashboard($request) === true))
        {
            $auth       = AuthType::PROXY_AUTH;
            $identifier = $key;
        }
        else if ((in_array($route, Route::$private, true) === true) and
            ($this->isDashboard($request) === false))
        {
            $auth       = AuthType::PRIVATE_AUTH;
            $identifier = $key;
        }
        else if (in_array($route, Route::$public, true) === true)
        {
            $auth       = AuthType::PUBLIC_AUTH;
            $identifier = $key;
        }
        else if (in_array($route, Route::$publicCallback, true) === true)
        {
            $auth       = AuthType::PUBLIC_AUTH;
            $identifier = $key;
        }
        else if (in_array($route, Route::$proxy, true) === true)
        {
            $auth       = AuthType::PROXY_AUTH;
            $identifier = $key;
        }
        else if (in_array($route, Route::$device, true) === true)
        {
            $auth       = AuthType::DEVICE_AUTH;
            $identifier = $secret;
        }
        else if (in_array($route, Route::$direct, true) === true)
        {
            $auth       = AuthType::DIRECT_AUTH;
            $identifier = '';
        }

        return [$mode, $auth, $identifier];
    }

    private function isDashboard(Request $request): bool
    {
        return ($this->appsConfig['dashboard']['secret'] === $request->getPassword());
    }
}
