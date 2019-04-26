<?php

namespace RZP\Models\Gateway\Downtime;

use App;
use Predis\Pipeline\Pipeline;
use Razorpay\Hodor\LeakyBucket;
use Illuminate\Redis\RedisManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Models\Admin\ConfigKey;

/**
 * Throttle gateway exceptions for downtime creation
 *
 * Approach:
 *
 * 1. Get settings from redis. These includes global settings and
 *     per method/gateway settings for each mode.
 * 2. From settings above, prepare throttle key (usually just gateway)
 *     and limits (leak rate, duration and burst) and call throttle
 *     package to check if it should be allowed.
 *
 */
class GatewayErrorThrottler
{
    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var RedisManager
     */
    protected $redis;

    /**
     * @var array
     */
    protected $settings;

    const SETTINGS_KEY               = ConfigKey::DOWNTIME_THROTTLE;

    const SKIP                       = 'skip';
    const MAX_BUCKET_SIZE            = 'mbs';
    const LEAK_RATE_VALUE            = 'lrv';
    const LEAK_RATE_DURATION         = 'lrd';

    const DEFAULT_SKIP               = true;
    const DEFAULT_MAX_BUCKET_SIZE    = 30;
    const DEFAULT_LEAK_RATE_VALUE    = 2;
    const DEFAULT_LEAK_RATE_DURATION = 1;

    public function __construct(string $gateway, string $method = null)
    {
        /** @var $app Application */
        $app = App::getFacadeRoot();

        $this->trace   = $app['trace'];

        $this->redis   = Redis::connection()->client();

        $this->gateway = $gateway;

        $this->method  = $method;

        $this->mode    = $app['rzp.mode'];
    }

    public function attempt(): bool
    {
        // Default value is true, since 'allowing' gateway
        // errors here means not acting upon them
        // (and not creating unnecessary gateway downtimes)
        $allowed = true;

        try
        {
            $this->initThrottleSettings();

            $allowed = $this->attemptThrottleAndReturnAllowedIfApplicable();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        return $allowed;
    }

    protected function initThrottleSettings()
    {
        $settings = $this->loadSettingsFromRedis();

        if (empty($settings) === true)
        {
            // Alert for manual action if no configuration exists, continues the flow with code defaults.
            $this->trace->warning(TraceCode::GATEWAY_DOWNTIME_THROTTLE_SETTINGS_MISSING);
        }

        $this->settings = $settings;
    }

    protected function loadSettingsFromRedis(): array
    {
        return $this->redis->hgetall(self::SETTINGS_KEY);
    }

    protected function attemptThrottleAndReturnAllowedIfApplicable(): bool
    {
        if ($this->isThrottleSkipped() === true)
        {
            return true;
        }

        return $this->attemptThrottleAndReturnAllowed();
    }

    protected function attemptThrottleAndReturnAllowed(): bool
    {
        $key              = $this->getThrottleKey();
        $leakRateValue    = $this->getThrottleLeakRateValue();
        $leakRateDuration = $this->getThrottleLeakRateDuration();
        $maxBucketSize    = $this->getThrottleMaxBucketSize();

        $limiter = new LeakyBucket\Redis($maxBucketSize, $leakRateValue, $leakRateDuration, $this->redis);
        $limiter->setPrefix(self::SETTINGS_KEY.':key:');
        $response = $limiter->attempt($key);

        if ($response->allowed === false)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_THROTTLE_DISALLOWED, [
                'key'                => $key,
                'leak_rate_value'    => $leakRateValue,
                'leak_rate_duration' => $leakRateDuration,
                'max_bucket_size'    => $maxBucketSize,
                'response'           => $response,
            ]);
        }

        return $response->allowed;
    }

    protected function getThrottleKey(): string
    {
        $args = [
            $this->mode,
            $this->gateway,
        ];

        if ($this->method !== null)
        {
            $args[] = $this->method;
        }

        return implode(':', $args);
    }

    protected function isThrottleSkipped(): bool
    {
        return $this->getThrottleValue(self::SKIP, self::DEFAULT_SKIP);
    }

    protected function getThrottleLeakRateValue(): int
    {
        return $this->getThrottleValue(self::LEAK_RATE_VALUE, self::DEFAULT_LEAK_RATE_VALUE);
    }

    protected function getThrottleLeakRateDuration(): int
    {
        return $this->getThrottleValue(self::LEAK_RATE_DURATION, self::DEFAULT_LEAK_RATE_DURATION);
    }

    protected function getThrottleMaxBucketSize(): int
    {
        return $this->getThrottleValue(self::MAX_BUCKET_SIZE, self::DEFAULT_MAX_BUCKET_SIZE);
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
        // Key: downtime:throttle
        // Value: {
        //      // Globals
        //      skip                                   => 1
        //      lrv                                    => 2
        //      lrd                                    => 1
        //      mbs                                    => 30
        //
        //      // Per mode. Expected in most cases, as live and test gateway
        //      // will very likely have different error thresholds, and because
        //      // in test mode we only use Sharp on prod anyway
        //
        //      <mode>:skip                            => 1
        //      <mode>:mock                            => 1
        //      <mode>:lrv                             => 2
        //      <mode>:lrd                             => 1
        //      <mode>:mbs                             => 30
        //
        //      // Per method. Expected to be used because card failure tolerance
        //      // might be lower than UPI, since we have more options
        //
        //      <mode>:<method>:skip                   => 1
        //      <mode>:<method>:mock                   => 1
        //      <mode>:<method>:lrv                    => 2
        //      <mode>:<method>:lrd                    => 1
        //      <mode>:<method>:mbs                    => 30
        //
        //      // Per gateway. Expected to be used when a certain
        //      // gateway exhibits higher or lower tolerance than others.
        //
        //      <mode>:<method>:<gateway>:skip         => 1
        //      <mode>:<method>:<gateway>:mock         => 1
        //      <mode>:<method>:<gateway>:lrv          => 2
        //      <mode>:<method>:<gateway>:lrd          => 1
        //      <mode>:<method>:<gateway>:mbs          => 30
        // }
        //

        $mode    = $this->mode;
        $gateway = $this->gateway;
        $method  = $this->method;

               // The most specific value, using method and gateway both
        return $this->settings["{$mode}:{$method}:{$gateway}:{$key}"] ??
               // Value for just mode and method
               $this->settings["{$mode}:{$method}:{$key}"] ??
               // Value for just mode (expected to be most commonly used)
               $this->settings["{$mode}:{$key}"] ??
               // Global default value
               $this->settings["{$key}"] ??
               // Default by calling function
               $default;
    }
}
