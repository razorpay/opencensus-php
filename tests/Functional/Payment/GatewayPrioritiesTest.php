<?php

namespace RZP\Tests\Functional\Payment;

use Redis;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class GatewayPrioritiesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayPrioritiesTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testSaveGatewayPriorities()
    {
        Redis::shouldReceive('zadd')
            ->once()
            ->andReturnUsing(function ()
            {
                return 5;
            });

        $this->startTest();
    }

    public function testSaveGatewayPrioritiesWithException()
    {
        Redis::shouldReceive('zadd')
            ->once()
            ->andReturnUsing(function ()
            {
                return -1;
            });

        $this->startTest();
    }

    public function testSaveGatewayPrioritiesWithRedisException()
    {
        Redis::shouldReceive('zadd')
                ->once()
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testFetchGatewayPriorities()
    {
        config(['services.redis_store.mock' => false]);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priorities:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc' => '50',
                    'axis_migs' => '40',
                    'amex' => '30',
                    'cybersource' => '20',
                    'first_data' => '10'
                ];
            });

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priorities:netbanking', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'ebs' => '50',
                    'billdesk' => '40'
                ];
            });

        $this->startTest();
    }

    public function testFetchGatewayPrioritiesWithException()
    {
        config(['services.redis_store.mock' => false]);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priorities:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc' => '50',
                    'axis_migs' => '40',
                    'amex' => '30',
                    'cybersource' => '20',
                    'first_data' => '10'
                ];
            });

        Redis::shouldReceive('zrevrange')
                ->once()
                ->with('gateway_priorities:netbanking', 0, -1, 'WITHSCORES')
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testRemoveGatewayPriorities()
    {
        config(['services.redis_store.mock' => false]);

        Redis::shouldReceive('zrem')
            ->once()
            ->with('gateway_priorities:card', 'hdfc')
            ->andReturn(null);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priorities:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'axis_migs' => '40',
                    'amex' => '30',
                    'cybersource' => '20',
                    'first_data' => '10'
                ];
            });

        $this->startTest();
    }

    public function testRemoveGatewayPrioritiesWithException()
    {
        Redis::shouldReceive('zrem')
                ->once()
                ->with('gateway_priorities:card', 'hdfc')
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testUnsupportedPaymentMethod()
    {
        $this->startTest();
    }

    public function testInvalidGatewayForMethod()
    {
        $this->startTest();
    }
}
