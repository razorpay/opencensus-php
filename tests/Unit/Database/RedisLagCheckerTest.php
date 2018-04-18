<?php

namespace RZP\Tests\Unit\Database;

use Cache;
use Razorpay\Trace\Facades\Trace;
use Doctrine\DBAL\Driver\PDOConnection;

use RZP\Tests\TestCase;
use RZP\Models\Admin\ConfigKey;
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
            return new PDOConnection('mysql:host=localhost;port=3306;dbname=api_test', 'root', 'root');
        });

        $this->assertInstanceOf(PDOConnection::class, $result);
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
            return new PDOConnection('mysql:host=localhost;port=3306;dbname=api_test', 'root', 'root');
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
            return new PDOConnection('mysql:host=localhost;port=3306;dbname=api_test', 'root', 'root');
        });

        $this->assertNull($result);
    }
}

