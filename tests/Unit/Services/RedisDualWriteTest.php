<?php

namespace RZP\Tests\Unit\Services;
namespace RZP\Services;

use Redis;

use RZP\Tests\TestCase;

class RedisDualWriteTest extends TestCase
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
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])->getMock();

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

    public function testMutexReleaseMethod()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get', 'del', 'ttl', 'set'])->getMock();
        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(3))->method('get')->will($this->returnValue($requestId));
        $redisMock->expects($this->exactly(2))->method('del')->will($this->returnValue(1));
        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        Redis::shouldReceive('connection')
            ->times(6)
            ->andReturn($redisMock);

        $response = $this->mutex->release('test');

        $this->assertTrue($response);
    }

    public function testMutexReleaseMethodException()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get'])->getMock();
        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(1))->method('get')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        Redis::shouldReceive('connection')
            ->times(2)
            ->andReturn($redisMock);

        $response = $this->mutex->release('test');

        $this->assertTrue($response);
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

        $response = $this->mutex->acquire('test', 60);

        $this->assertTrue($response);

        $response = $this->mutex->release('test', 60);

        $this->assertTrue($response);

    }

    public function testMutexDelMethodException()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get', 'del', 'set', 'ttl'])->getMock();

        $requestId = $this->app['request']->getId();

        $redisMock->expects($this->exactly(3))->method('get')->will($this->returnValue($requestId));

        $redisMock->expects($this->exactly(1))->method('del')->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->expects($this->exactly(1))->method('ttl')->will($this->returnValue(60));

        $redisMock->expects($this->exactly(2))->method('set')->will($this->returnValue(true));

        Redis::shouldReceive('connection')
            ->times(8)
            ->andReturn($redisMock);

        $response = $this->mutex->release('test');

        $this->assertTrue($response);
    }
}
