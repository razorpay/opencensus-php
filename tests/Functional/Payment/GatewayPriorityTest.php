<?php

namespace RZP\Tests\Functional\Payment;

use Redis;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\DataStore\PrioritySet\Implementation\Redis as StoreImplementation;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class GatewayPriorityTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayPriorityTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testSaveGatewayPriority()
    {
        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

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
        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

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
        config(['app.data_store.mock' => false]);

        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

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
        config(['app.data_store.mock' => false]);

        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

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
        config(['app.data_store.mock' => false]);

        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

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
        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

        Redis::shouldReceive('zrem')
                ->once()
                ->with('gateway_priority:card', ['hdfc'])
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        $this->startTest();
    }

    public function testUpdateGatewayPriority()
    {
        // Setting the config to false here so that real service is used for fetch
        config(['app.data_store.mock' => false]);

        $redis = Redis::getFacadeRoot();

        Redis::shouldReceive('connection')
            ->with('query_cache_test')
            ->andReturn($redis);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturnUsing(function ()
            {
                return 6;
            });

        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc'        => '60',
                    'axis_migs'   => '50',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10',
                ];
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

    public function testAuthorizePaymentWithRedisException()
    {
        config(['app.data_store.mock' => false]);

        $this->fixtures->create('terminal:all_shared_terminals');

        $storeStub = $this->createMock(StoreImplementation::class);

        $storeStub->method('fetchOrFail')
                    ->will($this->throwException(new Exception\ServerErrorException(
                                    'Error fetching from redis',
                                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION)));

        $this->defaultAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
    }
}
