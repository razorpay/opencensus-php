<?php

namespace RZP\Tests;

use Illuminate\Support\Facades\Redis;

use RZP\Http\Throttle\Constant as K;

abstract class AbstractThrottleTest extends TestCase
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

    protected function setRedisGlobalSettings(int $skip = 0, int $mock = 0)
    {
        $args = ['skip', $skip, 'mock', $mock];
        $this->redis->hmset(K::GLOBAL_SETTINGS_KEY, ...$args);
    }

    protected function flushRedis()
    {
        $this->redis->flushall();
    }
}
