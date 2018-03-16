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
use RZP\Foundation\Application;
use RZP\Exception\BlockException;
use RZP\Exception\ThrottleException;
use RZP\Http\Throttle\Constant as K;

/**
 * Throttle requests to API
 *
 * Approach:
 * 1. Extract needed vars from requests. E.g. mode, route name, authentication
 * mode, merchant id, key id, oauth application id etc.
 * 2. Get settings from redis. These includes global settings, per route
 * settings and per identifier (e.g. specific merchant, specific oauth
 * application, specific admin email and other various combinations).
 * 3. From settings above and available requests context vars, prepare throttle
 * key and limits (leak rate, duration and burst) and call throttle package.
 *
 * Caveats:
 * 1. Because we get key id instead of mid(merchant id) in public and private
 * authentication mode we do a translation (redis hit else db call). This is
 * a decision taken considering pros/cons(details in spec). We use mid to have
 * all the settings(if any) and also it's easy to deal with one identifier than
 * two.
 *
 * Redis: In the whole process we end up making 3 redis call(all the time). In
 * case of cache miss for key id to mid remap there is 1 db call involved.
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
    protected $reqctx;

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
        $this->reqctx = $app['request.ctx'];
    }

    public function throttle($request)
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
        return $this->reqctx->getInternalAppName() ?:
                $this->reqctx->getAdminEmail() ?:
                $this->reqctx->getOauthClientId() ?:
                $this->reqctx->getMid() ?:
                '';
    }

    protected function getThrottleKey(): string
    {
        $id = $this->reqctx->getInternalAppName() ?:
                $this->reqctx->getAdminEmail() ?:
                $this->reqctx->getMid() ?:
                $this->reqctx->getOauthPublicToken();

        // Only use ip address for public and direct routes
        $ip = ($this->reqctx->isPublicAuth() or $this->reqctx->isDirectAuth()) ? $this->reqctx->getRequest()->ip() : '';

        // E.g.: payments_create:live:private:0::10000000000000:
        $args = [
            $this->reqctx->getRoute(),
            $this->reqctx->getMode(),
            $this->reqctx->getAuth(),
            (int) $this->reqctx->getProxy(),
            $this->reqctx->getOauthClientId(),
            $id,
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
        // TODO: Move the data structure explanation to wiki

        // If mode is not available at this layer just pick live mode settings
        $mode  = $this->reqctx->getMode() ?: Mode::LIVE;
        // Boolean value doesn't get type-casted to string properly
        $auth  = $this->reqctx->getAuth();
        $proxy = (int) $this->reqctx->getProxy();
        $route = $this->reqctx->getRoute();

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
