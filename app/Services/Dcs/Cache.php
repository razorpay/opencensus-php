<?php


namespace RZP\Services\Dcs;



use Razorpay\Dcs\CacheInterface;
use Razorpay\Trace\Facades\Trace;

class Cache implements CacheInterface
{
    private $cache;

    public function __construct()
    {
        $this->cache = $this->getCache();
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        $value = $this->cache->get($key);

        $message = [
            'action' => 'cache_get',
            'key'    => $key,
            'value'  => $value,
        ];

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        $this->cache->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = 0)
    {
        $message = [
            'action' => 'cache_set',
            'key'    => $key,
            'value'  => $value,
            'ttl'    => $ttl,
        ];

        $this->cache->put($key, $value, $ttl);
    }

    /**
     * Returns the configured Laravel Cache Store
     *
     * @return mixed
     */
    protected function getCache()
    {
        return app('cache')->store('file');
    }
}

