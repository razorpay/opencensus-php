<?php

namespace RZP\Http\Throttle;

use App;
use Illuminate\Http\Request;
use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
use RZP\Exception\ThrottleException;

class Throttle
{
    use HasRequestContext;

    private $config;
    private $applications;
    private $trace;
    private $router;
    private $repo;
    private $redis;
    private $settings;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->config       = $app['config']->get('throttle');
        $this->applications = $app['config']->get('applications');
        $this->trace        = $app['trace'];
        $this->router       = $app['router'];
        $this->repo         = $app['repo'];
    }

    public function throttle($request): array
    {
        // Usually in local or test ENV we skip basis local configuration
        if ($this->config['skip'] === true)
        {
            return [];
        }

        try
        {
            $this->initRedisConnection();
            $this->initRequestContextVars($request);
            $this->initThrottleSettings();

            return $this->attemptThrottle();
        }
        catch (ThrottleException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
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

        $this->settings = $this->redis->pipeline(
            function ($pipe)
            {
                $pipe->hgetall(Constant::GLOBAL_SETTINGS_KEY);
                $pipe->hgetall(Constant::ROUTE_SETTINGS_KEY_REFIX . $this->route);
                $pipe->hgetall(Constant::IDENTIFIER_SETTINGS_KEY_PREFIX . $this->getIdentifier());
            });
    }

    private function setMidIfApplicable()
    {
        if (empty($this->keyId) === true)
        {
            return;
        }

        $key = Constant::JUST_ANOTHER_PREFIX . $this->key;
        $mid = $this->redis->get($key);

        if (empty($mid) === true)
        {
            $mid = $this->repo->key->connection($this->mode)->findOrFail($this->key)->getMerchantId();
            $this->redis->setex($key, 604800, $mid);
        }

        $this->mid = $mid;
    }

    private function attemptThrottle(): array
    {
        $allowed = 1;
        $limits  = [];

        // Throttling and blocking may be temporarily skipped via remote configuration
        if (($this->settings[0]['skip'] ?? '0') === '1') { return $limits; }

        $key              = $this->getThrottleKey();
        $leakRateValue    = $this->getThrottleRateValue();
        $leakRateDuration = $this->getThrottleRateDuration();
        $maxBucketSize    = $this->getThrottleMaxBucketSize();

        $limiter  = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $response = $limiter->attempt($key);
        $allowed  = array_shift($response);
        $limits   = $response;
        $payload  = compact('key', 'allowed', 'limits'); // For trace

        if ($allowed === 0)
        {
            throw new ThrottleException($limits[3], $payload);
        }

        $this->trace->debug(TraceCode::THROTTLE_DEBUG_LIMITS, $payload);

        return $limits;
    }

    private function getIdentifier(): string
    {
        return $this->device ??
                $this->adminEmail ??
                $this->internalAppName ??
                $this->oauthAppId ??
                $this->mid ??
                '';
    }

    private function getThrottleKey(): string
    {
        return "{$this->route}:{$this->mode}:{$this->auth}:{$this->getIdentifier()}:".
                // Adds IP address only for public authentications, in other
                // cases we have some identifier e.g. mid, email etc.
                ($this->isPrivateAuth() ? '' : $this->request->ip());
    }

    private function getThrottleRateValue(): int
    {
        return $this->getThrottleValue(Constant::LEAK_RATE_VALUE, 2);
    }

    private function getThrottleRateDuration(): int
    {
        return $this->getThrottleValue(Constant::LEAK_RATE_DURATION, 1000);
    }

    private function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValue(Constant::MAX_BUCKET_SIZE, 30);
    }

    private function getThrottleValue(string $key, int $default): int
    {
                // Value for given route, mode, auth & identifier combination
        return $this->settings[1]["{$this->mode}:{$this->auth}:{$this->getIdentifier()}:l:{$key}"] ??
                // Else value for given route, mode & auth combination
                $this->settings[1]["{$this->mode}:{$this->auth}:l:{$key}"] ??
                // Else value for given mode & auth combination
                $this->settings[0]["{$this->mode}:{$this->auth}:l:{$key}"] ??
                // Else the default value
                $default;
    }
}
