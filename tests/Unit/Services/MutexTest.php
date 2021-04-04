<?php

namespace RZP\Tests\Unit\Services;
namespace RZP\Services;

use Redis;

use RZP\Error\ErrorCode;
use RZP\Tests\TestCase;

class MutexTest extends TestCase
{
    protected $mutex = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutex = $this->app['api.mutex'];

        $this->mutex->setRedisClient(new RedisDualWrite($this->app));
    }

    public function testMutexAcquireMethod()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])->getMock();

        $redisMock->expects($this->exactly(2))->method('set')->will($this->returnValue(true));

        Redis::shouldReceive('connection')
            ->times(4)
            ->andReturn($redisMock);

        $response = $this->mutex->acquire('test', 60);

        $this->assertTrue($response);
    }

    public function testMutexAcquireMethodException()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['set', 'del', 'get'])->getMock();

        $redisMock->expects($this->exactly(1))->method('set')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->expects($this->exactly(2))->method('del')->will($this->returnValue(true));

        Redis::shouldReceive('connection')
            ->times(4)
            ->andReturn($redisMock);

        $response = $this->mutex->acquire('test', 60);

        $this->assertTrue($response);
    }

    public function testMutexStrictAcquireMethodException()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get', 'del'])->getMock();

        $redisMock->expects($this->exactly(1))->method('set')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->expects($this->exactly(2))->method('del')->will($this->returnValue(true));

        Redis::shouldReceive('connection')
            ->times(4)
            ->andReturn($redisMock);

        $response = $this->mutex->acquire('test', 60, 0, 100, 200, true);

        $this->assertFalse($response);
    }

    public function testMutexAcquireAndRelease()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set', 'del', 'ttl'])->getMock();

        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(2))->method('set')->will($this->returnValue(true));

        $redisMock->expects($this->exactly(5))->method('get')->will($this->returnValue($requestId));

        $redisMock->expects($this->exactly(2))->method('del')->will($this->returnValue(1));

        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        Redis::shouldReceive('connection')
            ->times(10)
            ->andReturn($redisMock);

        $response = $this->mutex->acquireAndRelease('test', function () {return true;});

        $this->assertTrue($response);
    }

    public function testMutexAcquireAndReleaseException()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set', 'del', 'ttl'])->getMock();

        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(1))->method('set')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->expects($this->exactly(5))->method('get')->will($this->returnValue($requestId));

        $redisMock->expects($this->exactly(4))->method('del')->will($this->returnValue(1));

        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        Redis::shouldReceive('connection')
            ->times(10)
            ->andReturn($redisMock);

        $response = $this->mutex->acquireAndRelease('test', function () {return true;});

        $this->assertTrue($response);
    }

    public function testMutexStrictAcquireAndReleaseException()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set', 'del', 'ttl'])->getMock();

        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(1))->method('set')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->expects($this->exactly(5))->method('get')->will($this->returnValue($requestId));

        $redisMock->expects($this->exactly(4))->method('del')->will($this->returnValue(1));

        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        Redis::shouldReceive('connection')
            ->times(10)
            ->andReturn($redisMock);

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
    }

    public function testMutexStrictAcquireAndReleaseExceptionWithRetry()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set', 'del', 'ttl'])->getMock();

        $requestId = $this->app['request']->getId();

        $matcher = $this->any();

        $redisMock
            ->expects($matcher)
            ->method('set')
            ->will($this->returnCallback(function() use($matcher) {
                switch ($matcher->getInvocationCount())
                {
                    case 1:
                        throw new \Predis\Response\ServerException('Internal Error');
                    default:
                        return true;
                }
              }));

        $redisMock->expects($this->exactly(7))->method('get')->will($this->returnValue($requestId));

        $redisMock->expects($this->exactly(4))->method('del')->will($this->returnValue(1));

        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        Redis::shouldReceive('connection')
            ->times(14)
            ->andReturn($redisMock);

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
    }
}
