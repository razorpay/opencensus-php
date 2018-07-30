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

    public function __construct()
    {
        /** @var $app Application */
        $app = App::getFacadeRoot();

        $this->config = $app['config']->get('throttle');
        $this->trace  = $app['trace'];
        $this->reqCtx = $app['request.ctx'];
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
        $this->redis = Redis::connection('throttle')->client();
    }

    protected function initThrottleSettings()
    {
        $settings = $this->loadSettingsFromRedis();

        if (empty(array_filter($settings)) === true)
        {
            $this->trace->critical(TraceCode::THROTTLE_SETTINGS_MISSING);
        }

        list($this->settings[K::GLOBAL], $this->settings[K::ID_LEVEL]) = $settings;
    }

    protected function loadSettingsFromRedis(): array
    {
        return $this->redis->pipeline(
            function ($pipe)
            {
                /** @var $pipe Pipeline */
                $pipe->hgetall(K::GLOBAL_SETTINGS_KEY);
                $pipe->hgetall(K::ID_SETTINGS_KEY_PREFIX . $this->getIdSettingsKey());
            });
    }

    protected function blockIfApplicable()
    {
        if ($this->isBlocked() === true)
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

        $limiter  = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $response = $limiter->attempt($key);

        // Payload for trace and exception extra data
        $payload  = compact('key', 'leakRateValue', 'leakRateDuration', 'maxBucketSize', 'response');

        if ($response->allowed === false)
        {
            if ($this->isThrottleMocked() === false)
            {
                throw new ThrottleException($response->retryAfter, $payload);
            }

            $this->trace->critical(TraceCode::THROTTLE_REQUEST_THROTTLED, $payload);
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

        // Only use ip address for public and direct routes
        $ip = ($this->reqCtx->isPublicAuth() or $this->reqCtx->isDirectAuth()) ? $this->reqCtx->getRequest()->ip() : '';

        // E.g.: payments_create:live:private:0::10000000000000:
        $args = [
            $this->reqCtx->getRoute(),
            $this->reqCtx->getMode(),
            $this->reqCtx->getAuth(),
            (int) $this->reqCtx->getProxy(),
            $this->reqCtx->getOAuthClientId(),
            $id,
            $this->reqCtx->getUserId(),
            $ip
        ];

        return implode(':', $args);
    }

    protected function isBlocked(): bool
    {
        return $this->getThrottleValue(K::BLOCK, K::DEFAULT_BLOCK);
    }

    protected function isThrottleSkipped(): bool
    {
        return $this->getThrottleValue(K::SKIP, K::DEFAULT_SKIP);
    }

    protected function isThrottleMocked(): bool
    {
        return $this->getThrottleValue(K::MOCK, K::DEFAULT_MOCK);
    }

    protected function getThrottleLeakRateValue(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_VALUE, K::DEFAULT_LEAK_RATE_VALUE);
    }

    protected function getThrottleLeakRateDuration(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_DURATION, K::DEFAULT_LEAK_RATE_DURATION);
    }

    protected function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValue(K::MAX_BUCKET_SIZE, K::DEFAULT_MAX_BUCKET_SIZE);
    }

    protected function getThrottleValue(string $key, int $default): int
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
}
