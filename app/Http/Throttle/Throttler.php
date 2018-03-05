<?php

namespace RZP\Http\Throttle;

use App;
use Predis\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Redis\RedisManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Base\RepositoryManager;
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

    /**
     * @var bool
     */
    protected $skip;

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
        $this->skip               = ($this->config['skip'] === true);
    }

    public function throttle($request)
    {
        // Usually in local or test ENV we skip basis local configuration
        if ($this->skip === true)
        {
            return;
        }

        try
        {
            $this->initRequestContextVars($request);
            $this->initRedisConnection();
            $this->setMidIfApplicable();
            $this->initThrottleSettings();
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

        list($this->settings[K::GLOBAL], $this->settings[K::ID_LEVEL]) = $settings;

        // If settings is not found raise an alert and enable skip flag.
        if (empty($this->settings[K::GLOBAL]) === true)
        {
            $this->trace->critical(TraceCode::THROTTLE_SETTINGS_MISSING);
            $this->skip = true;
        }
        // Else set skip appropriately basis redis settings
        else
        {
            $this->skip = (($this->settings[K::GLOBAL]['skip'] ?? '0') === '1');
        }
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

    protected function attemptThrottleIfApplicable()
    {
        // Throttling and blocking may be temporarily skipped via remote configuration(Redis)
        if ($this->skip === true)
        {
            return;
        }

        $this->attemptThrottle();
    }

    protected function attemptThrottle()
    {
        $key              = $this->getThrottleKey();
        $leakRateValue    = $this->getThrottleRateValue();
        $leakRateDuration = $this->getThrottleRateDuration();
        $maxBucketSize    = $this->getThrottleMaxBucketSize();

        $limiter  = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $response = $limiter->attempt($key);

        // Payload for trace and exception extra data
        $payload  = compact('key', 'leakRateValue', 'leakRateDuration', 'maxBucketSize', 'response');

        // Only throttle if it is not in mock mode(early release)
        $mock = $this->settings[K::GLOBAL]['mock'] ?? '1';
        if (($response->allowed === false) and ($mock === '0'))
        {
            throw new ThrottleException($response->retryAfter, $payload);
        }

        $this->trace->debug(TraceCode::THROTTLE_ATTEMPT_RESPONSE, $payload);
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
        return implode(':', [$this->route, $this->mode, $this->auth, (int) $this->proxy, $this->oauthAppId, $id, $ip]);
    }

    protected function getThrottleRateValue(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_VALUE, 2);
    }

    protected function getThrottleRateDuration(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_DURATION, 1);
    }

    protected function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValue(K::MAX_BUCKET_SIZE, 30);
    }

    protected function getThrottleValue(string $key, int $default): int
    {
        //
        // Redis data structures:
        //
        // Key: t
        // Value: {
        //      skip:                       1
        //      mock:                       1
        //
        //      <mode>:<auth>:lrv:          2
        //      <mode>:<auth>:lrd:          1
        //      <mode>:<auth>:mbs:          30
        //
        //      <mode>:<auth>:<route>:lrv:  2
        //      <mode>:<auth>:<route>:lrd:  1
        //      <mode>:<auth>:<route>:mbs:  30
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
                // Value for given mode, auth & route
                $this->settings[K::GLOBAL]["{$this->mode}:{$this->auth}:{$proxy}:{$this->route}:{$key}"] ??
                // Value for given mode & auth
                $this->settings[K::GLOBAL]["{$this->mode}:{$this->auth}:{$proxy}:{$key}"] ??
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
