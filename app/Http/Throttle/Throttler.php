<?php

namespace RZP\Http\Throttle;

use App;
use Predis\Pipeline\Pipeline;
use Razorpay\Hodor\LeakyBucket;
use Illuminate\Redis\RedisManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestContext;
use RZP\Foundation\Application;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\BlockException;
use RZP\Exception\ThrottleException;
use RZP\Http\Throttle\Constant as K;

/**
 * Throttle requests to API
 *
 * Approach:
 *
 * 1. Get settings from redis. These includes global settings, per route
 *    settings and per identifier (e.g. specific merchant, specific oauth
 *    application, specific admin email and other various combinations).
 * 2. From settings above and available requests context vars, prepare throttle
 *    key and limits (leak rate, duration and burst) and call throttle package.
 *
 */
class Throttler
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var RequestContext
     */
    protected $reqCtx;

    /**
     * @var RedisManager
     */
    protected $redis;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var boolean
     */
    protected $runningUnitTests;

    public function __construct()
    {
        /** @var $app Application */
        $app = App::getFacadeRoot();

        $this->config           = $app['config']->get('throttle');
        $this->trace            = $app['trace'];
        $this->reqCtx           = $app['request.ctx'];
        $this->runningUnitTests = $app->runningUnitTests();
    }

    public function throttle()
    {
        // For local and test env, we skip basis local configuration
        if ($this->config['skip'] === true)
        {
            return;
        }


        try
        {
            // Update: Now for private/proxy/public requests  for key and oauth based we do throttling at nginx
            // openresty layer itself and hence must not repeat here.
            // Keeping this flow in unit tests still.
            // Moving this logic here as blocking is not supported in nginx layer as of now.
            // Following Not handled in nginx layer
            //   - partner/direct/admin/app auth
            //   - Keyless public auth
            if (($this->reqCtx->isNginxHandledAuthFlowType() === true) and
                ($this->reqCtx->isNginxHandledAuthType() === true) and
                ($this->runningUnitTests === false))
            {
                return;
            }

            // skip if idSettingKey is empty or request is made from internal app
            if ((empty($this->getIdSettingsKey()) === true) or
                (empty($this->reqCtx->getInternalAppName()) === false))
            {
                return;
            }

            $this->initRedisConnection();
            $this->initThrottleSettings();
            $this->blockIfApplicable();

            $this->attemptThrottleIfApplicable();
        }
        catch (\Throwable $e)
        {
            if ($e instanceof ThrottleException)
            {
                throw $e;
            }

            $this->trace->traceException($e);
        }
    }

    protected function initRedisConnection()
    {
        $this->redis = Redis::connection('mutex_redis')->client();
    }

    protected function initThrottleSettings()
    {
        $settings = $this->loadSettingsFromRedis();

        if (empty(array_filter($settings)) === true)
        {
            // Alert for manual action if no configuration exists, continues the flow with code defaults.
            $this->trace->critical(TraceCode::THROTTLE_SETTINGS_MISSING);
        }

        list($this->settings[K::GLOBAL], $this->settings[K::ID_LEVEL]) = $settings;
    }

    protected function loadSettingsFromRedis(): array
    {
        $settings = array();

        array_push($settings, $this->redis->hgetall(K::GLOBAL_SETTINGS_KEY));
        array_push($settings, $this->redis->hgetall(K::ID_SETTINGS_KEY_PREFIX . $this->getIdSettingsKey()));

        return $settings;
    }

    protected function blockIfApplicable()
    {
        if ($this->isBlocked() === true)
        {
            throw new BlockException(null, ['key' => $this->getThrottleKey()]);
        }

        $this->blockByIpIfApplicable();
        $this->blockByUserAgentIfApplicable();
    }

    /**
     * Blocks current request if IP exclusion rule exists for the same in redis config.
     */
    protected function blockByIpIfApplicable()
    {
        $ip         = $this->reqCtx->getRequest()->ip();
        $blockedIPs = $this->getBlockedIPs();

        if (empty($blockedIPs) === true)
        {
            return;
        }

        // For IP, do exact match
        $wrappedIp  = str_wrap($ip, K::LIST_DELIMITER);
        $blockedIPs = str_wrap($blockedIPs, K::LIST_DELIMITER);

        if (str_contains($blockedIPs, $wrappedIp) === true)
        {
            throw new BlockException(null, ['key' => $this->getThrottleKey()]);
        }

    }

    /**
     * Blocks current request if UA exclusion rule exists for the same in redis config.
     */
    protected function blockByUserAgentIfApplicable()
    {
        $userAgent = $this->reqCtx->getRequest()->userAgent();
        $blockedUserAgents = $this->getBlockedUserAgents();

        if (empty($blockedUserAgents) === true)
        {
           return;
        }

        // For user agents, match just the beginning
        $wrappedUserAgent  = str_start($userAgent, K::LIST_DELIMITER);
        $blockedUserAgents = str_wrap($blockedUserAgents, K::LIST_DELIMITER);

        if (str_contains($blockedUserAgents, $wrappedUserAgent) === true)
        {
            throw new BlockException(null, ['key' => $this->getThrottleKey()]);
        }

    }

    protected function attemptThrottleIfApplicable()
    {
        if ($this->isThrottleSkipped() === false)
        {
            $this->attemptThrottle();
        }
    }

    protected function attemptThrottle()
    {
        $key              = $this->getThrottleKey();
        $leakRateValue    = $this->getThrottleLeakRateValue();
        $leakRateDuration = $this->getThrottleLeakRateDuration();
        $maxBucketSize    = $this->getThrottleMaxBucketSize();

        $limiter = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $limiter->setPrefix('throttle:pv:');
        $response = $limiter->attempt($key);

        // Payload for trace and exception extra data
        $payload = compact('key', 'leakRateValue', 'leakRateDuration', 'maxBucketSize', 'response');

        if ($response->allowed === false)
        {
            if ($this->isThrottleMocked() === false)
            {
                throw new ThrottleException($response->retryAfter, $payload);
            }

            $shouldTraceMock = ConfigKey::get(ConfigKey::THROTTLE_MOCK_LOG_VERBOSE, true);

            $shouldTraceMock = (bool) ($shouldTraceMock ?? true);

            if ($shouldTraceMock === true)
            {
                // For metrics purpose traces same info if throttling is mocked i.e. to not throw 429 actually.
                $this->trace->info(TraceCode::THROTTLE_REQUEST_THROTTLED_MOCK, $payload);
            }
        }
    }

    protected function getIdSettingsKey(): string
    {
        return $this->reqCtx->getOAuthClientId() ?:
               $this->reqCtx->getAdminEmail() ?:
               $this->reqCtx->getMid() ?:
               $this->reqCtx->getInternalAppName() ?:
               '';
    }

    protected function getThrottleKey(): string
    {
        $id = $this->reqCtx->getMid() ?:
              $this->reqCtx->getAdminEmail() ?:
              $this->reqCtx->getOAuthPublicToken() ?:
              $this->reqCtx->getInternalAppName();

        // Only use ip address for 1) api's public, direct group routes, 2) dashboard_guest(internal) group routes
        $ip = (($this->reqCtx->isPublicAuth() === true) or
               ($this->reqCtx->isDirectAuth() === true) or
               ($this->reqCtx->isDashboardGuest() === true)) ? $this->reqCtx->getRequest()->ip() : '';

        // E.g.: payments_create:live:private:0::10000000000000:
        $args = [
            $this->reqCtx->getRoute(),
            $this->reqCtx->getMode(),
            $this->reqCtx->getAuth(),
            (int) $this->reqCtx->getProxy(),
            $this->reqCtx->getOAuthClientId(),
            $id,
            $this->reqCtx->getUserId(),
            $ip,
        ];

        $extraArgs = $this->getExtraThrottleKeyArgsFromConfig();
        if (count($extraArgs) > 0)
        {
            array_push($args, ...$extraArgs);
        }

        return implode(':', $args);
    }

    protected function isBlocked(): bool
    {
        return $this->getThrottleValueAsInt(K::BLOCK, K::DEFAULT_BLOCK);
    }

    protected function isThrottleSkipped(): bool
    {
        return $this->getThrottleValueAsInt(K::SKIP, K::DEFAULT_SKIP);
    }

    protected function isThrottleMocked(): bool
    {
        return $this->getThrottleValueAsInt(K::MOCK, K::DEFAULT_MOCK);
    }

    protected function getThrottleLeakRateValue(): int
    {
        return $this->getThrottleValueAsInt(K::LEAK_RATE_VALUE, K::DEFAULT_LEAK_RATE_VALUE);
    }

    protected function getThrottleLeakRateDuration(): int
    {
        return $this->getThrottleValueAsInt(K::LEAK_RATE_DURATION, K::DEFAULT_LEAK_RATE_DURATION);
    }

    protected function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValueAsInt(K::MAX_BUCKET_SIZE, K::DEFAULT_MAX_BUCKET_SIZE);
    }

    protected function getBlockedIPs(): string
    {
        return $this->getThrottleValueAsString(K::BLOCKED_IPS, K::DEFAULT_BLOCKED_IPS);
    }

    protected function getBlockedUserAgents(): string
    {
        return $this->getThrottleValueAsString(K::BLOCKED_USER_AGENTS, K::DEFAULT_BLOCKED_USER_AGENTS);
    }

    protected function getThrottleValueAsInt(string $key, int $default): int
    {
        return $this->getThrottleValue($key, $default);
    }

    protected function getThrottleValueAsString(string $key, string $default): string
    {
        return $this->getThrottleValue($key, $default);
    }

    /**
     * Gets configuration value for given key from redis config cascadingly.
     * @param  string     $key
     * @param  int|string $default
     * @return int|string
     */
    protected function getThrottleValue(string $key, $default)
    {
        //
        // Redis data structures which is used in cascading fashion to get
        // values for given request context.
        //
        // Key: t
        // Value: {
        //      // Globals
        //      skip:                                1
        //      mock:                                1
        //      lrv:                                 2
        //      lrd:                                 1
        //      mbs:                                 30
        //
        //      // Per mode
        //      <mode>:skip:                         1
        //      <mode>:mock:                         1
        //      <mode>:lrv:                          2
        //      <mode>:lrd:                          1
        //      <mode>:mbs:                          30
        //
        //      // Per auth
        //      <mode>:<auth>:<proxy>:skip:          0
        //      <mode>:<auth>:<proxy>:mock:          0
        //      <mode>:<auth>:<proxy>:block:         1 (Block)
        //      <mode>:<auth>:<proxy>:lrv:           2
        //      <mode>:<auth>:<proxy>:lrd:           1
        //      <mode>:<auth>:<proxy>:mbs:           30
        //
        //      <mode>:<auth>:<proxy>:blocked_ips:         ip1||ip2||ip3
        //      <mode>:<auth>:<proxy>:blocked_user_agents: ua1||ua2||ua3
        //
        //      // Per auth, per route
        //      <mode>:<auth>:<proxy>:<route>:skip:  0
        //      <mode>:<auth>:<proxy>:<route>:mock:  0
        //      <mode>:<auth>:<proxy>:<route>:block: 0 (Block)
        //      <mode>:<auth>:<proxy>:<route>:lrv:   2
        //      <mode>:<auth>:<proxy>:<route>:lrd:   1
        //      <mode>:<auth>:<proxy>:<route>:mbs:   30
        // }
        //
        // Key: t:i:<mid>
        // Value: {
        //      -- Same setting as above - across or per route
        // }
        //
        // Key: t:i:<oauthClientId>
        // Value: {
        //      -- Same setting as above - across or per route
        //      (Applies to the application + mid combination)
        // }
        //

        // If mode is not available at this layer just pick live mode settings
        $mode  = $this->reqCtx->getMode() ?: Mode::LIVE;
        // Boolean value doesn't get type-casted to string properly
        $auth  = $this->reqCtx->getAuth();
        $proxy = (int) $this->reqCtx->getProxy();
        $route = $this->reqCtx->getRoute();

                // Value for given mid/application id, mode, auth & route
        return $this->settings[K::ID_LEVEL]["{$mode}:{$auth}:{$proxy}:{$route}:{$key}"] ??
               // Value for given mid/application id, mode & auth
               $this->settings[K::ID_LEVEL]["{$mode}:{$auth}:{$proxy}:{$key}"] ??
               // Value for given mid/application id & mode
               $this->settings[K::ID_LEVEL]["{$mode}:{$key}"] ??
               // Value for given mid/application id
               $this->settings[K::ID_LEVEL]["{$key}"] ??
               // Value for given mode, auth & route
               $this->settings[K::GLOBAL]["{$mode}:{$auth}:{$proxy}:{$route}:{$key}"] ??
               // Value for given mode & auth
               $this->settings[K::GLOBAL]["{$mode}:{$auth}:{$proxy}:{$key}"] ??
               // Value for given mode
               $this->settings[K::GLOBAL]["{$mode}:{$key}"] ??
               // Finally, global default value
               $this->settings[K::GLOBAL]["{$key}"] ??
               // Again finally, the default by callee :)
               $default;
    }

    /**
     * Returns extra arguments to be used for throttle identifier for specific route as configured
     * @return array
     */
    protected function getExtraThrottleKeyArgsFromConfig(): array
    {
        $route  = $this->reqCtx->getRoute();
        $config = $this->config['throttle_key'][$route] ?? null;

        if ($config === null)
        {
            return [];
        }

        $routeParams   = $config['route_params'] ?? [];
        $requestParams = $config['request_params'] ?? [];
        $headerParams  = $config['header_params'] ?? [];

        $args = [];

        $request = $this->reqCtx->getRequest();

        foreach ($routeParams as $k)
        {
            $args[] = $request->route()->parameter($k);
        }

        foreach ($requestParams as $k)
        {
            $args[] = $request->input($k);
        }

        foreach ($headerParams as $k)
        {
            $args[] = $request->headers->get($k);
        }

        return $args;
    }
}
