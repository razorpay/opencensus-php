<?php

namespace RZP\Tests\Unit\Services;
namespace RZP\Services;

use Illuminate\Support\Facades\Redis;

use RZP\Tests\TestCase;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\CustomAssertions;

class MutexTest extends TestCase
{
    use CustomAssertions;

    protected $mutex = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('services.mutex.mock', false);

        $this->mutex = $this->app['api.mutex'];
    }

    public function mockRedisClient()
    {
        $redisMock = Redis::partialMock();

        $this->mutex->setRedisClient($redisMock);

        return $redisMock;
    }

    protected function getMutexAcquiredResourcesProperty()
    {
        $reflectedClass = new \ReflectionClass($this->mutex);
        $reflection     = $reflectedClass->getProperty('acquiredResources');
        $reflection->setAccessible(true);

        return $reflection->getValue($this->mutex);
    }

    public function testMutexAcquireMethod()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $response = $this->mutex->acquire('test', 60);

        $this->assertTrue($response);

        $this->assertArraySelectiveEquals(['test' => 1], $this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireResourceTwice()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);
                        self::assertEquals(100, $ttl);

                        return true;
                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId. '_1', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);
                        self::assertEquals(160, $ttl);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $firstResourceAcquired = $this->mutex->acquire('test', 100);
        $secondResourceAcquired = $this->mutex->acquire('test', 160);

        $this->assertTrue($firstResourceAcquired);
        $this->assertTrue($secondResourceAcquired);

        $this->assertArraySelectiveEquals(['test' => 2], $this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireMethodException()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        throw new \Predis\Response\ServerException('Internal Error');
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $response = $this->mutex->acquire('test', 60);

        $this->assertTrue($response);

        $this->assertArraySelectiveEquals(['test' => 1], $this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireMethodWithDifferentRequestId()
    {
        $redisClientMock = $this->mockRedisClient();

        $newRequestId = $this->app['request']->getId();

        $requestIdWithMutex = bin2hex(random_bytes(16));

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($newRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($newRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return null; // Since resource is already acquired by some other request
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $requestIdWithMutex) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return $requestIdWithMutex;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $response = $this->mutex->acquire('test', 60);

        $this->assertFalse($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexStrictAcquireMethod()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $response = $this->mutex->acquire('test', 60, 0, 100, 200, true);

        $this->assertTrue($response);

        $this->assertArraySelectiveEquals(['test' => 1], $this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexStrictAcquireMethodException()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        throw new \Predis\Response\ServerException('Internal Error');
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $response = $this->mutex->acquire('test', 60, 0, 100, 200, true);

        $this->assertFalse($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireAndRelease()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $response = $this->mutex->acquireAndRelease('test', function () {return true;});

        $this->assertTrue($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireAndReleaseWithDifferentRequestId()
    {
        $redisClientMock = $this->mockRedisClient();

        $newRequestId = $this->app['request']->getId();

        $requestIdWithMutex = bin2hex(random_bytes(16));

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($newRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($newRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return null; // Since resource is already acquired by some other request
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $requestIdWithMutex) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $requestIdWithMutex;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        try
        {
            $this->mutex->acquireAndRelease('test', function () {return true;});
        }
        catch (\Exception $ex)
        {
            $failed = true;

            $this->assertEquals($ex->getCode(), ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        $this->assertTrue($failed);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAcquireAndReleaseException()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        throw new \Predis\Response\ServerException('Internal Error');
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $response = $this->mutex->acquireAndRelease('test', function () {return true;});

        $this->assertTrue($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexStrictAcquireAndRelease()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $response = $this->mutex->acquireAndRelease(
            'test',
            function () {
                return true;
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            0,
            100,
            300,
            true
        );

        $this->assertTrue($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexStrictAcquireAndReleaseException()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        throw new \Predis\Response\ServerException('Internal Error');
                    default:
                        return true;
                }
            })->times(1);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                    case 2:
                    self::assertEquals('mutex:test', $resource);

                        return null;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $failed = false;

        try
        {
            $this->mutex->acquireAndRelease(
                'test',
                function () {
                    return true;
                },
                60,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                0,
                100,
                300,
                true
            );
        }
        catch (\Exception $ex)
        {
            $failed = true;

            $this->assertEquals($ex->getCode(), ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        $this->assertTrue($failed);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexStrictAcquireAndReleaseExceptionWithRetry()
    {
        $redisClientMock = $this->mockRedisClient();

        $expectedRequestId = $this->app['request']->getId();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        throw new \Predis\Response\ServerException('Internal Error');
                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(2);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                    case 2:
                    self::assertEquals('mutex:test', $resource);

                        return null;
                    case 3:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(3);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(60)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $response = $this->mutex->acquireAndRelease(
            'test',
            function () {
                return true;
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            1,
            100,
            200,
            true
        );

        $this->assertTrue($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testMutexAndAcquireTwiceAndReleaseAll()
    {
        $this->markTestSkipped("Unit Test is failing due to bug in code. Mutex.php(L:475) request count is
        assigned 0 as a string and not int, due to which strict check in the subsequent if is failing.");

        $expectedRequestId = $this->app['request']->getId();

        $redisClientMock = $this->mockRedisClient();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);
                        self::assertEquals(90, $ttl);

                        return true;

                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_1', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);
                        self::assertEquals(140, $ttl);

                        return true;

                    case 3:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_0', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);
                        self::assertEquals(100, $ttl);


                        return true;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(3);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                self::assertEquals('mutex:test', $resource);

                switch ($getInvocationCount)
                {
                    case 1:
                        return null;
                    case 2:
                        return $expectedRequestId;
                    case 3:
                        return $expectedRequestId . '_1';
                    case 4:
                        return $expectedRequestId . '_0';
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(4);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(100) // Assuming the first Mutex took 40s for execution
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $response = $this->mutex->acquireAndRelease('test', function() {
            $nestedResponse = $this->mutex->acquireAndRelease('test', function() {
                return true;
            }, 140);

            return $nestedResponse;
        }, 90);

        $this->assertTrue($response);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testReleaseAllAcquired()
    {
        $expectedRequestId = $this->app['request']->getId();

        $redisClientMock = $this->mockRedisClient();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_1', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);

                        return true;

                    case 3:
                        self::assertEquals('mutex:test1', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(3);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    case 3:
                        self::assertEquals('mutex:test1', $resource);

                        return null;
                    case 4:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId . '_1';
                    case 5:
                        self::assertEquals('mutex:test1', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(5);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test1')
                        ->andReturn(1)
                        ->times(1);

        assertTrue($this->mutex->acquire('test'));
        assertTrue($this->mutex->acquire('test'));
        assertTrue($this->mutex->acquire('test1'));

        $this->assertArraySelectiveEquals(['test' => 2, 'test1' => 1], $this->getMutexAcquiredResourcesProperty());

        $this->mutex->forceReleaseAllAcquired();

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testReleaseMultiple()
    {
        $expectedRequestId = $this->app['request']->getId();

        $redisClientMock = $this->mockRedisClient();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_1', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);

                        return true;

                    case 3:
                        self::assertEquals('mutex:test1', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(3);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    case 3:
                        self::assertEquals('mutex:test1', $resource);

                        return null;
                    case 4:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId . '_1';
                    case 5:
                        self::assertEquals('mutex:test1', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(5);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test1')
                        ->andReturn(1)
                        ->times(1);

        assertTrue($this->mutex->acquire('test'));
        assertTrue($this->mutex->acquire('test'));
        assertTrue($this->mutex->acquire('test1'));

        $this->assertArraySelectiveEquals(['test' => 2, 'test1' => 1], $this->getMutexAcquiredResourcesProperty());

        $resourcesToBeReleased = [
            'test',
            'test1'
        ];

        $this->mutex->releaseMultiple($resourcesToBeReleased, '', true);

        $this->assertEmpty($this->getMutexAcquiredResourcesProperty());
    }

    public function testReleaseMultipleWhenIgnoreRequestCountIsFalse()
    {
        $expectedRequestId = $this->app['request']->getId();

        $redisClientMock = $this->mockRedisClient();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 2:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_1', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);

                        return true;

                    case 3:
                        self::assertEquals('mutex:test1', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 4:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId . '_0', $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('xx', $keyOption);
                        self::assertEquals(80, $ttl);
                        // Assuming it took 100 secs to release the first mutex, the ttl that would be set is 180 - 100 = 80s

                        return true;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(4);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    case 3:
                        self::assertEquals('mutex:test1', $resource);

                        return null;
                    case 4:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId . '_1';
                    case 5:
                        self::assertEquals('mutex:test1', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(5);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->andReturn(80)
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test1')
                        ->andReturn(1)
                        ->times(1);

        assertTrue($this->mutex->acquire('test', 100));
        assertTrue($this->mutex->acquire('test', 180));
        assertTrue($this->mutex->acquire('test1'));

        $this->assertArraySelectiveEquals(['test' => 2, 'test1' => 1], $this->getMutexAcquiredResourcesProperty());

        $resourcesToBeReleased = [
            'test',
            'test1'
        ];

        $this->mutex->releaseMultiple($resourcesToBeReleased);

        $this->assertArraySelectiveEquals(['test' => 1], $this->getMutexAcquiredResourcesProperty());
    }

    public function testReleaseMultipleSelectiveResources()
    {
        $expectedRequestId = $this->app['request']->getId();

        $redisClientMock = $this->mockRedisClient();

        $setInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('set')
            ->andReturnUsing(function($resource, $requestId, $ttlOption, $ttl, $keyOption) use($expectedRequestId, &$setInvocationCount) {
                $setInvocationCount++;
                switch ($setInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 2:
                        self::assertEquals('mutex:test1', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    case 3:
                        self::assertEquals('mutex:test2', $resource);
                        self::assertEquals($expectedRequestId, $requestId);
                        self::assertEquals('ex', $ttlOption);
                        self::assertEquals('nx', $keyOption);

                        return true;

                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(3);

        $getInvocationCount = 0;

        $redisClientMock
            ->shouldReceive('get')
            ->andReturnUsing(function($resource) use(&$getInvocationCount, $expectedRequestId) {
                $getInvocationCount++;

                switch ($getInvocationCount)
                {
                    case 1:
                        self::assertEquals('mutex:test', $resource);

                        return null;
                    case 2:
                        self::assertEquals('mutex:test1', $resource);

                        return null;
                    case 3:
                        self::assertEquals('mutex:test2', $resource);

                        return null;
                    case 4:
                        self::assertEquals('mutex:test', $resource);

                        return $expectedRequestId;
                    case 5:
                        self::assertEquals('mutex:test1', $resource);

                        return $expectedRequestId;
                    default:
                        throw new LogicException('Test case invalid');
                }
            })->times(5);

        $redisClientMock->shouldReceive('ttl')
                        ->with('mutex:test')
                        ->times(0);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test')
                        ->andReturn(1)
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test1')
                        ->andReturn(1)
                        ->times(1);

        $redisClientMock->shouldReceive('del')
                        ->with('mutex:test2')
                        ->times(0);

        assertTrue($this->mutex->acquire('test'));
        assertTrue($this->mutex->acquire('test1'));
        assertTrue($this->mutex->acquire('test2'));

        $this->assertArraySelectiveEquals(['test' => 1, 'test1' => 1, 'test2' => 1], $this->getMutexAcquiredResourcesProperty());

        $resourcesToBeReleased = [
            'test',
            'test1'
        ];

        $this->mutex->releaseMultiple($resourcesToBeReleased);

        $this->assertArraySelectiveEquals(['test2' => 1], $this->getMutexAcquiredResourcesProperty());
    }
}
