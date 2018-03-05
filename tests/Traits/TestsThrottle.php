<?php

namespace RZP\Tests\Traits;

use Illuminate\Support\Facades\Redis;

use RZP\Http\Throttle\Constant as K;

/**
 * Trait provides setup and tear methods which configures throttle
 * redis instance etc for use and provides a few helper methods.
 */
trait TestsThrottle
{
    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * Setups:
     * - Disables local skip flag
     * - Initialize redis
     * - Set default global settings
     */
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('throttle.skip', false);
        $this->initRedisConnection();
        $this->setRedisGlobalSettings();
    }

    /**
     * Tears down:
     * - Enables local skip flag
     * - Flushes redis
     */
    public function tearDown()
    {
        $this->app['config']->set('throttle.skip', true);
        $this->flushRedis();

        parent::tearDown();
    }

    protected function initRedisConnection()
    {
        $this->redis = Redis::connection('throttle')->client();
    }

    protected function setRedisGlobalSettings(array $parameters = ['skip' => 0, 'mock' => 0])
    {
        $this->setRedisSettings(K::GLOBAL_SETTINGS_KEY, $parameters);
    }

    protected function setRedisIdLevelSettings(string $id, array $parameters = [])
    {
        $this->setRedisSettings(K::ID_SETTINGS_KEY_PREFIX . $id, $parameters);
    }

    protected function setRedisSettings(string $key, array $parameters)
    {
        if (empty($parameters) === false)
        {
            $this->redis->hmset($key, ...seq_array($parameters));
        }
        // If parameters is empty, it implies need to unset the key
        else
        {
            $this->redis->del($key);
        }
    }

    protected function flushRedis()
    {
        $this->redis->flushall();
    }
}
