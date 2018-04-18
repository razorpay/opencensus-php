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
            'flag' => ConfigKey::SKIP_SLAVE,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::SKIP_SLAVE)
                ->andReturn(false);

        $result = $lagChecker->useReadPdoIfApplicable(function ()
        {
            return new MockPDO();
        });

        $this->assertInstanceOf(PDO::class, $result);
    }

    public function testReturnsNullWhenFlagSet()
    {
        $lagChecker = new RedisLagChecker([
            'flag' => ConfigKey::SKIP_SLAVE,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::SKIP_SLAVE)
                ->andReturn(true);

        $result = $lagChecker->useReadPdoIfApplicable(function ()
        {
            return new MockPDO();
        });

        $this->assertNull($result);
    }

    public function testReturnsNullOnCacheException()
    {
        $lagChecker = new RedisLagChecker([
            'flag' => ConfigKey::SKIP_SLAVE,
        ]);

        Cache::shouldReceive('get')
                ->once()
                ->with(ConfigKey::SKIP_SLAVE)
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

