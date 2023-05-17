<?php


namespace RZP\Services\Dcs;

use App;
use Razorpay\Dcs\CacheInterface;
use RZP\Trace\TraceCode;

class Cache implements CacheInterface
{
    private $cache;
    private $app;
    private $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->cache = $this->app['cache'];
        $this->trace = $this->app['trace'];
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }

    /**
     * @inheritdoc
     */
    public function remove($key): void
    {
        $this->cache->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = 0): void
    {
        $message = [
            'action' => 'cache_set',
            'key'    => $key,
            'ttl'    => $ttl,
        ];

        $this->trace->info(
            TraceCode::REDIS_KEY_SET, $message
        );

        $this->cache->put($key, $value, $ttl);
    }
}

