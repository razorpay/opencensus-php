<?php

namespace RZP\Http\Throttle;

use App;
use Predis\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Razorpay\Hodor\LeakyBucket;
use Illuminate\Redis\RedisManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Exception\BlockException;
use RZP\Exception\ThrottleException;
use RZP\Http\Throttle\Constant as K;
use RZP\Exception\BadRequestException;

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
    use HasRequestContext;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $applications;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var RedisManager
     */
    protected $redis;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $isRunningUnitTests;

    public function __construct()
    {
        /** @var $app Application */
        $app = App::getFacadeRoot();

        $this->config             = $app['config']->get('throttle');
        $this->applications       = $app['config']->get('applications');
        $this->trace              = $app['trace'];
        $this->router             = $app['router'];
        $this->repo               = $app['repo'];
        $this->isRunningUnitTests = $app->runningUnitTests();
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
            $this->initRequestContextVars($request);
            $this->initRedisConnection();
            $this->setMidIfApplicable();
            $this->initThrottleSettings();
            $this->blockIfApplicable();
            $this->attemptThrottleIfApplicable();
        }
        catch (\Throwable $e)
        {
            if (($e instanceof ThrottleException) or
                ($e instanceof BadRequestException))
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
            throw new BlockException();
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
        return $this->internalAppName ?:
                $this->adminEmail ?:
                $this->oauthAppId ?:
                $this->mid ?:
                '';
    }

    protected function getThrottleKey(): string
    {
        $id = $this->internalAppName ?:
                $this->adminEmail ?:
                $this->mid ?:
                $this->oauthPublicToken;

        // Only use ip address for public and direct routes
        $ip = ($this->isPublicAuth() or $this->isDirectAuth()) ? $this->request->ip() : '';

        // E.g.: payments_create:live:private:0::10000000000000:
        $args = [$this->route, $this->mode, $this->auth, (int) $this->proxy, $this->oauthAppId, $id, $ip];
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
        // Key: t:i:<oauthappid>
        // Value: {
        //      -- Same setting as above - across or per route
        //      (Applies to the application + mid combination)
        // }
        //

        // Boolean value doesn't get type-casted to string properly
        $proxy = (int) $this->proxy;

                // Value for given mid/application id, mode, auth & route
        return $this->settings[K::ID_LEVEL]["{$this->mode}:{$this->auth}:{$proxy}:{$this->route}:{$key}"] ??
                // Value for given mid/application id, mode & auth
                $this->settings[K::ID_LEVEL]["{$this->mode}:{$this->auth}:{$proxy}:{$key}"] ??
                // Value for given mid/application id & mode
                $this->settings[K::ID_LEVEL]["{$this->mode}:{$key}"] ??
                // Value for given mid/application id
                $this->settings[K::ID_LEVEL]["{$key}"] ??
                // Value for given mode, auth & route
                $this->settings[K::GLOBAL]["{$this->mode}:{$this->auth}:{$proxy}:{$this->route}:{$key}"] ??
                // Value for given mode & auth
                $this->settings[K::GLOBAL]["{$this->mode}:{$this->auth}:{$proxy}:{$key}"] ??
                // Value for given mode
                $this->settings[K::GLOBAL]["{$this->mode}:{$key}"] ??
                // Finally, global default value
                $this->settings[K::GLOBAL]["{$key}"] ??
                // Again finally, the default by callee :)
                $default;
    }

    /**
     * Sets mid if key id is available so only mid gets used
     * to retrieve settings and further in throttle key.
     */
    protected function setMidIfApplicable()
    {
        if (empty($this->keyId) === true)
        {
            return;
        }

        $key = K::KEYID_MID_KEY_PREFIX . $this->keyId;
        $mid = $this->redis->get($key);

        if (empty($mid) === true)
        {
            $mid = $this->getMidForKeyIdFromDb();
            $this->redis->setex($key, K::NUM_SECONDS_IN_WEEK, $mid);
        }

        $this->mid = $mid;
    }

    protected function getMidForKeyIdFromDb()
    {
        return $this->repo->key->connection($this->mode)->findOrFailPublic($this->keyId)->getMerchantId();
    }
}
