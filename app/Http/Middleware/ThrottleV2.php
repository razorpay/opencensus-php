<?php

namespace RZP\Http\Middleware;

use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Application;
use Illuminate\Http\{Request, JsonResponse as Response};

use ApiResponse;
use RZP\Constants\Mode;
use RZP\Http\{Route, RequestHeader};
use RZP\Exception\{ThrottleException, BlockedException};
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Throttles requests to API
 *
 * -----------------------------------------------------------------------------
 * Approach:
 * - For every incoming request we identify route, mode, auth and a identifier.
 *   - route:       Name of the route
 *   - mode:        Application mode (live|test)
 *   - identifier:  An user identifier (who is being throttled) - e.g. Merchant
 *                  key, Admin user email etc. This same gets used in constructing
 *                  final REDIS key which gets throttled(e.g. identifier + ip address etc)
 * - In REDIS we keep some dynamic configurations(explained below), we fetch that
 *   and prepare throttle parameters (key which gets throttled, and limits values
 *   (leak rate, duration , burst)).
 * - We use leaky bucket implementation to finally throttle using above parameters.
 *
 * -----------------------------------------------------------------------------
 *
 * Settings in REDIS:
 *
 * - Global settings:
 *   - Keeps global settings for throttle.
 *
 *   {
 *       skip:                                  0|1,
 *       <mode>:<auth>:limits:leak_rate:        100,
 *       <mode>:<auth>:limits:max_bucket_size:  30,
 *   }
 *
 * - Route level settings:
 *   - Keeps route level settings for throttle & block, e.g. route specific limits
 *   - Also for some routes and identifier combination can increase/decrease the
 *     limit and also block them.
 *
 * - Identifier level settings:
 *   - Keeps identifier level settings for throttle & block.
 *
 * -----------------------------------------------------------------------------
 */
final class ThrottleV2
{
    // Keys, prefix for keeping settings in REDIS
    const THROTTLE_SETTINGS_KEY_1        = 'throttle';
    const THROTTLE_SETTINGS_KEY_2_PREFIX = 'throttle:route:';
    const THROTTLE_SETTINGS_KEY_3_PREFIX = 'throttle:identifier:';
    // REDIS key parts (middle, suffix etc)
    const LIMITS                         = 'limits';
    const BLOCKED                        = 'blocked';
    const LEAK_RATE_VALUE                = 'leak_rate_value';
    const LEAK_RATE_DURATION             = 'leak_rate_duration';
    const MAX_BUCKET_SIZE                = 'max_bucket_size';

    private $config;
    private $appsConfig;
    private $trace;
    private $router;
    private $redis;

    public function __construct(Application $app)
    {
        $this->config     = $app['config']->get('throttle');
        $this->appsConfig = $app['config']->get('applications');
        $this->trace      = $app['trace'];
        $this->router     = $app['router'];
    }

    public function handle($request, \Closure $next)
    {
        $limits   = $this->throttle($request);
        $response = $next($request);

        return ApiResponse::withRateLimitHeaders($response, $limits);
    }

    private function throttle(Request $request): array
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
        if ($settings[0]['skip'] === '1')
        {
            return [];
        }

        $this->checkIfRequestIsBlocked($mode, $auth, $identifier, $settings);

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
                $pipe->hgetall(self::THROTTLE_SETTINGS_KEY_1);
                $pipe->hgetall(self::THROTTLE_SETTINGS_KEY_2_PREFIX . $route);
                $pipe->hgetall(self::THROTTLE_SETTINGS_KEY_3_PREFIX . $identifier);
            });
    }

    /**
     * Basis settings obtained from REDIS cascade check if the current requests
     * is blocked for current context.
     *
     * @param  string $mode
     * @param  string $auth
     * @param  string $identifier
     * @param  array  $settings
     *
     * @throws BlockedException
     *
     */
    private function checkIfRequestIsBlocked(string $mode, string $auth, string $identifier, array $settings)
    {
        // If Route is blocked
        $blocked = (empty($settings[1]['blocked']) === false) ||
        // Else if Rote is blocked on given mode
                    (empty($settings[1]["$mode:blocked"]) === false) ||
        // Else if Route is blocked on given mode, identifier
                    (empty($settings[1]["$mode:$identifier:blocked"]) === false) ||
        // Else if Identifier is blocked
                    (empty($settings[2]['blocked']) === false) ||
        // Else if Identifier is blocked for given mode
                    (empty($settings[2]["$mode:blocked"]) === false) ||
        // Else if Identifier is blocked for given mode, auth
                    (empty($settings[2]["$mode:$auth:blocked"]) === false);

        if ($blocked)
        {
            throw new BlockedException();
        }
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
        $key           = "$identifier$mode$route" . ($auth === AuthType::PUBLIC_AUTH ? $request->ip() : '');
        // Throttle defaults to "leak at the rate of 3 per sec and allows max burst of 50"
        $leakRate      = $this->getThrottleLimits(self::LEAK_RATE_VALUE, 3, $mode, $auth, $identifier, $settings);
        $leakDuration  = $this->getThrottleLimits(self::LEAK_RATE_DURATION, 1000, $mode, $auth, $identifier, $settings);
        $maxBucketSize = $this->getThrottleLimits(self::MAX_BUCKET_SIZE, 50, $mode, $auth, $identifier, $settings);

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
        return $this->redis ?: ($this->redis = Redis::connection($this->config['driver'])->client());
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
        $key  = $request->input('key_id') ?:
        // key can come as part of route parameters for callback URLS
                $this->router->current()->parameter('key') ?:
        // key for all other case comes as HTTP basic auth user name
                $request->getUser();

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
