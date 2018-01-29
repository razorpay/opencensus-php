<?php

namespace RZP\Http\Middleware;

use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Application;
use Illuminate\Http\{Request, JsonResponse as Response};

use ApiResponse;
use RZP\Http\{Route, RequestHeader};
use RZP\Exception\{ThrottleException, BlockedException};
use RZP\Http\BasicAuth\Type as AuthType;

final class ThrottleV2
{
    const THROTTLE_SETTINGS_KEY_1        = 'throttle';
    const THROTTLE_SETTINGS_KEY_2_PREFIX = 'throttle:route:';
    const THROTTLE_SETTINGS_KEY_3_PREFIX = 'throttle:identifier:';

    private $config;
    private $trace;
    private $router;
    private $redis;

    public function __construct(Application $app)
    {
        $this->config = $app['config']->get('throttle');
        $this->trace  = $app['trace'];
        $this->router = $app['router'];
        $this->redis  = Redis::connection($this->config['driver'])->client();
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
            return [1, []];
        }

        $route                          = $this->router->currentRouteName();
        list($mode, $auth, $identifier) = $this->getModeAuthAndIdentifier($request, $route);
        $settings                       = $this->getThrottleSettings($route, $identifier);

        // Throttling and blocking may be temporarily skipped via remote configuration
        if ($settings[0]['skip'] === 1)
        {
            return [1, []];
        }

        $this->checkIfRequestIsBlocked($mode, $auth, $identifier, $settings);

        return $this->throttleUsingLeakyBucket($request, $route, $mode, $auth, $identifier, $settings);
    }

    private function getThrottleSettings(string $route, string $identifier): array
    {
        return $this->redis->pipeline(
            function ($pipe)
            {
                $pipe->get(self::THROTTLE_SETTINGS_KEY_1);
                $pipe->get(self::THROTTLE_SETTINGS_KEY_2_PREFIX . $route);
                $pipe->get(self::THROTTLE_SETTINGS_KEY_3_PREFIX . $identifier);
            });
    }

    private function checkIfRequestIsBlocked(string $mode, string $auth, string $identifier, array $settings): array
    {
        $blocked = false ||
                    // Route is blocked
                    (empty($settings[1]['blocked']) === false) ||
                    // Rote is blocked on given mode
                    (empty($settings[1]["$mode:blocked"]) === false) ||
                    // Route is blocked on given mode, identifier
                    (empty($settings[1]["$mode:$identifier:blocked"]) === false) ||
                    // Identifier is blocked
                    (empty($settings[2]['blocked']) === false) ||
                    // Identifier is blocked for given mode
                    (empty($settings[2]["$mode:blocked"]) === false) ||
                    // Identifier is blocked for given mode, auth
                    (empty($settings[2]["$mode:$auth:blocked"]) === false);

        if ($blocked)
        {
            throw new BlockedException();
        }
    }

    private function getThrottleParameter()
    {
        // $key            = "$identifier$mode$route" . ($auth !== AuthType::PRIVATE_AUTH ? $request->ip() : '');
        // $refillRate     = null;
        // $refillDuration = 1000;
        // $burst          = null;

        // list($refillRate, $refillDuration, $burst) = $this->get
    }

    private function throttleUsingLeakyBucket(
        Request $request,
        string $route,
        string $mode,
        string $auth,
        string $identifier,
        array $settings): array
    {
        $key            = "$identifier$mode$route" . ($auth !== AuthType::PRIVATE_AUTH ? $request->ip() : '');
        $refillRate     = $this->getThrottleLimits('refill_rate_value', 3);
        $refillDuration = $this->getThrottleLimits('refill_rate_duration', 1000);
        $burst          = $this->getThrottleLimits('max_bucket_size', 50);

        $allowed = 1;
        $limits  = [];

        try
        {
            $limiter = new LeakyBucket\Redis($parameters[1], $parameters[2], $parameters[3], $this->redis);

            $response = $limiter->attempt($parameters[0]);
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

        return [$allowed, $limits];
    }

    private function getThrottleLimits(
        string $key,
        int $default,
        string $mode,
        string $auth,
        string $identifier,
        array $settings): int
    {
        return null ??
                $settings[0]["$mode:$auth:limits:$key"] ??
                $settings[1]["$mode:$auth:limits:$key"] ??
                $settings[1]["$mode:$auth:$identifier:limits:$key"] ??
                $default;
    }

    private function getModeAuthAndIdentifier(Request $request, string $route): array
    {
        $key  = $this->request->input('key_id') ?: $this->router->current()->parameter('key') ?: $request->getUser();
        $mode = substr($key, 4, 4);

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
        else if ((in_array($route, Route::$private, true) === true) and ($this->isDashboard() === true))
        {
            $auth       = AuthType::PROXY_AUTH;
            $identifier = $key;
        }
        else if ((in_array($route, Route::$private, true) === true) and ($this->isDashboard() === false))
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
}
