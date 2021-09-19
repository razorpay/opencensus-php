<?php

namespace RZP\Tests\Unit\Database;

use PDO;
use Cache;

use RZP\Tests\TestCase;
use RZP\Models\Admin\ConfigKey;
use Razorpay\Trace\Facades\Trace;
use RZP\Tests\Unit\Database\Helpers\MockPDO;
use RZP\Base\Database\LagChecker\RedisLagChecker;

class RedisLagCheckerTest extends TestCase
{
    public function testReturnsPdoConnectionOnSuccess()
    {
        $lagChecker = new RedisLagChecker([
                                              'flag'  => ConfigKey::MASTER_PERCENT,
                                              'read_from_config' => false,
                                              'percentage' => 0,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::MASTER_PERCENT)
                ->andReturn(false);

        $result = $lagChecker->useReadPdoIfApplicable(function ()
        {
            return new MockPDO();
        });

        if ($result instanceof  \Closure)
        {
            $result = call_user_func($result);
        }

        $this->assertInstanceOf(PDO::class, $result);
    }

    public function testReturnsPdoConnectionWhenInitializedConnectionPassed()
    {
        $lagChecker = new RedisLagChecker([
                                              'flag' => ConfigKey::MASTER_PERCENT,
                                              'read_from_config' => false,
                                              'percentage' => 0,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::MASTER_PERCENT)
                ->andReturn(0);

        $result = $lagChecker->useReadPdoIfApplicable(new MockPDO());

        $this->assertInstanceOf(PDO::class, $result);
    }

    public function testReturnsNullWhenFlagSet()
    {
        $lagChecker = new RedisLagChecker([
                                              'flag' => ConfigKey::MASTER_PERCENT,
                                              'read_from_config' => false,
                                              'percentage' => 0,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::MASTER_PERCENT)
                ->andReturn(100);

        $result = $lagChecker->useReadPdoIfApplicable(function ()
        {
            return new MockPDO();
        });

        $this->assertNull($result);
    }

    public function testReturnsNullOnCacheException()
    {
        $lagChecker = new RedisLagChecker([
                                              'flag' => ConfigKey::MASTER_PERCENT,
                                              'read_from_config' => false,
                                              'percentage' => 0,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::MASTER_PERCENT)
                ->andReturnUsing(function ()
                {
                    throw new \Exception('cache failure');
                });

        $result = $lagChecker->useReadPdoIfApplicable(function ()
        {
            return new MockPDO();
        });

        $this->assertNull($result);
    }
}
