<?php


namespace RZP\Services\CircuitBreaker\Store;

use App;
use Cache;
use Predis\Client;
use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;
use RZP\Services\CircuitBreaker\CircuitState;
use RZP\Services\CircuitBreaker\KeyHelper;

class RedisClusterStore implements StoreInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * Set settings for start circuit service
     *
     * @param $redis
     * @param string $redisNamespace
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $service
     * @return bool
     */
    public function isOpen(string $service): bool
    {
        return (bool) $this->redis->get(
            $this->makeNamespace($service) . ':open'
        );
    }

    /**
     * @param string $service
     * @param int $failureRateThreshold
     * @return bool
     */
    public function reachRateLimit(string $service, int $failureRateThreshold): bool
    {
        $failures = (int) $this->redis->get(
            $this->makeNamespace($service) . ':failures'
        );

        return ($failures >= $failureRateThreshold);
    }

    /**
     * @param string $service
     * @return bool|string
     */
    public function isHalfOpen(string $service): bool
    {
        return (bool) $this->redis->get(
            $this->makeNamespace($service) . ':half_open'
        );
    }

    /**
     * @param string $service
     * @param int $timeWindow
     * @return bool
     */
    public function incrementFailure(string $service, int $timeWindow): bool
    {
        $serviceName = $this->makeNamespace($service) . ':failures';

        if ( !$this->redis->get($serviceName)) {
            $this->redis->incr($serviceName);
            return (bool) $this->redis->expire($serviceName, $timeWindow);
        }

        return (bool) $this->redis->incr($serviceName);
    }

    /**
     * @param string $service
     */
    public function setSuccess(string $service): void
    {
        $serviceName = $this->makeNamespace($service);

        $this->redis->pipeline(function ($pipe) use ($serviceName) {
            $pipe->del($serviceName . ':open');
            $pipe->del($serviceName . ':failures');
            $pipe->del($serviceName . ':half_open');
        });
    }

    /**
     * @param string $service
     * @param int $timeWindow
     */
    public function setOpenCircuit(string $service, int $timeWindow): void
    {
        $this->redis->setex(
            $this->makeNamespace($service) . ':open',
            $timeWindow,
            time()
        );
    }

    /**
     * @param string $service
     * @param int $timeWindow
     * @param int $intervalToHalfOpen
     */
    public function setHalfOpenCircuit(string $service, int $timeWindow, int $intervalToHalfOpen): void
    {
        $this->redis->setex(
            $this->makeNamespace($service) . ':half_open',
            ($timeWindow + $intervalToHalfOpen),
            time()
        );
    }

    public function getFailuresCounter(string $service): int
    {
        $failures = $this->redis->get(
            $this->makeNamespace($service) . ':failures'
        );

        return (int) $failures;
    }

    /**
     * @param string $service
     * @return string
     */
    protected function makeNamespace(string $service): string
    {
        return 'circuit-breaker:' . $service;
    }
}
