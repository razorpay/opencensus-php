<?php

namespace RZP\Http\Throttle;

use App;
use Illuminate\Http\Request;
use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
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

    private $config;
    private $applications;
    private $trace;
    private $router;
    private $repo;
    private $redis;
    private $settings;
    private $isRunningUnitTests;

    public function __construct()
    {
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
        // Usually in local or test ENV we skip basis local configuration
        if ($this->config['skip'] === true)
        {
            return [];
        }

        try
        {
            $this->initRequestContextVars($request);
            $this->initRedisConnection();
            $this->initThrottleSettings();

            $this->attemptThrottle();
        }
        catch (\Throwable $e)
        {
            if (($e instanceof ThrottleException) or
                ($e instanceof BadRequestException))
            {
                throw $e;
            }

            $this->trace->traceException($e);

            return [];
        }
    }

    private function initRedisConnection()
    {
        $this->redis = Redis::connection('throttle')->client();
    }

    private function initThrottleSettings()
    {
        $this->setMidIfApplicable();

        $settings = $this->redis->pipeline(
            function ($pipe)
            {
                $pipe->hgetall(K::GLOBAL_SETTINGS_KEY);
                $pipe->hgetall(K::ID_SETTINGS_KEY_PREFIX . $this->getIdSettingsKey());
            });

        list($this->settings[K::GLOBAL], $this->settings[K::ID_LEVEL]) = $settings;
    }

    private function attemptThrottle()
    {
        $allowed = 1;
        $limits  = [];

        // Throttling and blocking may be temporarily skipped via remote configuration(Redis)
        if (($this->settings[K::GLOBAL]['skip'] ?? '0') === '1')
        {
            return $limits;
        }

        $key              = $this->getThrottleKey();
        $leakRateValue    = $this->getThrottleRateValue();
        $leakRateDuration = $this->getThrottleRateDuration();
        $maxBucketSize    = $this->getThrottleMaxBucketSize();

        $limiter  = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $response = $limiter->attempt($key);

        // Payload for trace and exception extra data
        $payload  = compact('key', 'leakRateValue', 'leakRateDuration', 'maxBucketSize', 'response');

        // Only throttle if it is not in mock mode(early release)
        $mock = $this->settings[K::GLOBAL]['mocked'] ?? '0';
        if (($response->allowed === 0) and ($mock === '0'))
        {
            throw new ThrottleException($response->retryAfter, $payload);
        }

        $this->trace->debug(TraceCode::THROTTLE_ATTEMPT_RESPONSE, $payload);
    }

    private function getIdSettingsKey(): string
    {
        return $this->internalAppName ?:
                $this->device ?:
                $this->adminEmail ?:
                $this->oauthAppId ?:
                $this->mid ?:
                '';
    }

    private function getThrottleKey(): string
    {
        $id = $this->internalAppName ?:
                $this->device ?:
                $this->adminEmail ?:
                $this->mid ?:
                $this->oauthPublicToken;

        $ip = $this->isPublicAuth() ? $this->request->ip() : '';

        // E.g.: payments_create:live:private:0::10000000000000:
        return implode(':', [$this->route, $this->mode, $this->auth, (int) $this->proxy, $this->oauthAppId, $id, $ip]);
    }

    private function getThrottleRateValue(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_VALUE, 2);
    }

    private function getThrottleRateDuration(): int
    {
        return $this->getThrottleValue(K::LEAK_RATE_DURATION, 1);
    }

    private function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValue(K::MAX_BUCKET_SIZE, 30);
    }

    private function getThrottleValue(string $key, int $default): int
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
        //      <mode>:<auth>:lrd:          1000
        //      <mode>:<auth>:mbs:          50
        //
        //      <mode>:<auth>:<route>:lrv:  2
        //      <mode>:<auth>:<route>:lrd:  1000
        //      <mode>:<auth>:<route>:mbs:  50
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
    private function setMidIfApplicable()
    {
        if (empty($this->keyId) === true)
        {
            return;
        }

        $key = K::KEYID_MID_KEY_PREFIX . $this->keyId;
        $mid = $this->redis->get($key);

        if (empty($mid) === true)
        {
            $mid = $this->repo->key->connection($this->mode)->findOrFailPublic($this->keyId)->getMerchantId();
            $this->redis->setex($key, K::NUM_SECONDS_IN_WEEK, $mid);
        }

        $this->mid = $mid;
    }
}
