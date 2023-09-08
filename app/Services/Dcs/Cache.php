<?php


namespace RZP\Services\Dcs;

use App;
use Razorpay\Dcs\CacheInterface;
use RZP\Constants\Environment;
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
        if (($this->app->isProduction() === true) or
            (($this->app['env'] === Environment::TESTING) or
                ($this->app['env'] === Environment::TESTING_DOCKER )))
        {
            return $this->cache->get($key);
        }

        return null;
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
        if (($this->app->isProduction() === true) or
            (($this->app['env'] === Environment::TESTING) or
                ($this->app['env'] === Environment::TESTING_DOCKER )))
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
}

