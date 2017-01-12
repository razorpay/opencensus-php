<?php

namespace RZP\Tests\Functional\Payment;

use Redis;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class GatewayPriorityTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayPriorityTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testSaveGatewayPriority()
    {
        Redis::shouldReceive('zadd')
            ->once()
            ->andReturnUsing(function ()
            {
                return 5;
            });

        $this->startTest();
    }

    public function testSaveGatewayPriorityWithException()
    {
        Redis::shouldReceive('zadd')
                ->once()
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testFetchGatewayPriority()
    {
        // Setting the config to false here so that real service is used for fetch
        config(['services.store.mock' => false]);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc'        => '50',
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ];
            });

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priority:netbanking', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'ebs'      => '50',
                    'billdesk' => '40'
                ];
            });

        $this->startTest();
    }

    public function testFetchGatewayPriorityWithException()
    {
        // Setting the config to false here so that real service is used for fetch
        config(['services.store.mock' => false]);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc'        => '50',
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ];
            });

        Redis::shouldReceive('zrevrange')
                ->once()
                ->with('gateway_priority:netbanking', 0, -1, 'WITHSCORES')
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testRemoveGatewayPriority()
    {
        // Setting the config to false here so that real service is used for fetch
        config(['services.store.mock' => false]);

        Redis::shouldReceive('zrem')
            ->once()
            ->with('gateway_priority:card', ['hdfc'])
            ->andReturn(null);

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ];
            });

        $this->startTest();
    }

    public function testRemoveGatewayPriorityWithException()
    {
        Redis::shouldReceive('zrem')
                ->once()
                ->with('gateway_priority:card', ['hdfc'])
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
