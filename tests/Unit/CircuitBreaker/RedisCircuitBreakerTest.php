<?php

namespace Unit\CircuitBreaker;

use RZP\Services\CircuitBreaker\KeyHelper;
use RZP\Services\CircuitBreaker\Store\CircuitBreakerRedisStore;
use RZP\Services\CircuitBreaker\CircuitState;
use RZP\Tests\TestCase;
use Illuminate\Support\Facades\Redis;

/**
 * Class RedisCircuitBreakerTest
 *
 * @package Tests\Unit\Adapter
 *
 */
class RedisCircuitBreakerTest extends TestCase
{
    /**
     * Test success incrementing a new failure to the counter for a micro-service.
     */
    public function testSuccessAddingNewFailureToAService()
    {
        $timeWindow = 40;
        $serviceName = 'SERVICE_NAME';
        $keyFailure = 'circuit_breaker:service:total_failures:11111';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyTotalFailuresToStore')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyFailure);


        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);


        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyFailure, true, $timeWindow)
                  ->andReturnTrue();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);

        $this->assertNull($redisCircuitBreaker->addFailure($serviceName, $timeWindow));
    }

    /**
     * Test error incrementing a new failure to the counter for a micro-service.
     */
    public function testRedisErrorAddingNewFailureToAService()
    {
        $timeWindow = 40;
        $serviceName = 'SERVICE_NAME';
        $keyFailure = 'circuit_breaker:service:total_failures:11111';
        $redisErrorMessage = 'UNEXPECTED_ERROR_MESSAGE';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyTotalFailuresToStore')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyFailure);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyFailure, true, $timeWindow)
                  ->andReturnFalse();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $redisCircuitBreaker->addFailure($serviceName, $timeWindow);
    }

    /**
     * Test success when opening the circuit.
     *
     */
    public function testSuccessOpeningCircuit()
    {
        $timeOpen = 40;
        $serviceName = 'SERVICE_NAME';
        $keyCircuitOpen = 'circuit_breaker:service:open';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyCircuitOpen);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyCircuitOpen, true, $timeOpen)
                  ->andReturnTrue();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $this->assertNull($redisCircuitBreaker->openCircuit($serviceName, $timeOpen));
    }

    /**
     * Test a Redis error when trying to open the circuit.
     *
     */
    public function testRedisErrorOpeningCircuit()
    {
        $timeOpen = 40;
        $serviceName = 'SERVICE_NAME';
        $keyCircuitOpen = 'circuit_breaker:service:open';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyCircuitOpen);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyCircuitOpen, true, $timeOpen)
                  ->andReturnFalse();


        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $redisCircuitBreaker->openCircuit($serviceName, $timeOpen);
    }

    /**
     * Test success when setting the circuit as half-open.
     *
     */
    public function testSuccessHalfOpenCircuit()
    {
        $timeOpen = 40;
        $serviceName = 'SERVICE_NAME';
        $keyHalfOpen = 'circuit_breaker:service:half_open';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyHalfOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyHalfOpen);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyHalfOpen, true, $timeOpen)
                  ->andReturnTrue();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $this->assertNull($redisCircuitBreaker->setCircuitHalfOpen($serviceName, $timeOpen));
    }

    /**
     * Test a Redis error when trying to set the circuit as half-open.
     *
     */
    public function testRedisErrorHalfOpenCircuit()
    {
        $timeOpen = 40;
        $serviceName = 'SERVICE_NAME';
        $keyCircuitHalfOpen = 'circuit_breaker:service:half_open';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyHalfOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyCircuitHalfOpen);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('set')
                  ->once()
                  ->with($keyCircuitHalfOpen, true, $timeOpen)
                  ->andReturnFalse();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $redisCircuitBreaker->setCircuitHalfOpen($serviceName, $timeOpen);
    }

    /**
     * Test success closing the circuit.
     *
     */
    public function testSuccessClosingCircuit()
    {
        $serviceName = 'SERVICE_NAME';
        $keyOpen = 'KEY_OPEN';
        $keyHalfOpen = 'KEY_HALF_OPEN';

        $keyTotalFailures = 'KEY_TOTAL_FAILURES';
        $keysFailuresToBeDeleted = ['K1', 'K2', 'K3'];
        $mergeKeysToDelete = array_merge([$keyOpen, $keyHalfOpen], $keysFailuresToBeDeleted);

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyOpen);

        $keyHelperMock->shouldReceive('generateKeyHalfOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyHalfOpen);

        $keyHelperMock->shouldReceive('generateKeyTotalFailuresToStore')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyTotalFailures);


        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('keys')
                  ->once()
                  ->with($keyTotalFailures)
                  ->andReturn($keysFailuresToBeDeleted);

        $redisMock->shouldReceive('del')
                  ->once()
                  ->with($mergeKeysToDelete)
                  ->andReturnTrue();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $this->assertNull($redisCircuitBreaker->closeCircuit($serviceName));
    }

    /**
     * Test a Redis error when trying to close the circuit.
     *
     */
    public function testRedisErrorClosingCircuit()
    {

        $serviceName = 'SERVICE_NAME';
        $keyOpen = 'KEY_OPEN';
        $keyHalfOpen = 'KEY_HALF_OPEN';

        $keyTotalFailures = 'KEY_TOTAL_FAILURES';
        $keysFailuresToBeDeleted = ['K1', 'K2', 'K3'];
        $mergeKeysToDelete = array_merge([$keyOpen, $keyHalfOpen], $keysFailuresToBeDeleted);

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyOpen);

        $keyHelperMock->shouldReceive('generateKeyHalfOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyHalfOpen);

        $keyHelperMock->shouldReceive('generateKeyTotalFailuresToStore')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyTotalFailures);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('keys')
                  ->once()
                  ->with($keyTotalFailures)
                  ->andReturn($keysFailuresToBeDeleted);

        $redisMock->shouldReceive('del')
                  ->once()
                  ->with($mergeKeysToDelete)
                  ->andReturnFalse();

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $this->assertNull($redisCircuitBreaker->closeCircuit($serviceName));
    }

    /**
     * Test method responsible for getting the total of failures for a service.
     */
    public function testGetTotalFailures()
    {
        $serviceName = 'SERVICE_NAME';
        $keyTotalFailures = 'circuit_breaker:service:total_failures:*';
        $arrayKeys = [
            'circuit_breaker:service:total_failures:134234',
            'circuit_breaker:service:total_failures:253243',
            'circuit_breaker:service:total_failures:433443'
        ];
        $expectedTotalFailures = sizeof($arrayKeys);

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyTotalFailuresToStore')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyTotalFailures);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('keys')
                  ->once()
                  ->with($keyTotalFailures)
                  ->andReturn($arrayKeys);

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $totalFailuresResult = $redisCircuitBreaker->getTotalFailures($serviceName);

        $this->assertEquals($expectedTotalFailures, $totalFailuresResult);
    }

    /**
     * Test when getting the current circuit state.
     *
     * @dataProvider dataProviderGetCircuitState
     *
     * @param CircuitState $expectedCircuitState
     * @param bool $isOpen Define if the circuit result (Redis) is open.
     * @param bool $isHalfOpen
     */
    public function testGettingTheCircuitState(
        string $expectedCircuitState,
        bool $isOpen,
        bool $isHalfOpen
    )
    {
        $serviceName = 'SERVICE_NAME';
        $keyOpen = 'KEY_OPEN';
        $keyHalfOpen = 'KEY_HALF_OPEN';

        $keyHelperMock = \Mockery::mock(KeyHelper::class);
        $keyHelperMock->shouldReceive('generateKeyOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyOpen);

        $keyHelperMock->shouldReceive('generateKeyHalfOpen')
                      ->once()
                      ->with($serviceName)
                      ->andReturn($keyHalfOpen);

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();

        Redis::shouldReceive('connection')
             ->times(4)
             ->andReturn($redisMock);

        $redisMock->shouldReceive('get')
                  ->with($keyHalfOpen)
                  ->andReturn($isHalfOpen);

        $redisMock->shouldReceive('get')
                  ->with($keyOpen)
                  ->andReturn($isOpen);

        $redisCircuitBreaker = new CircuitBreakerRedisStore($redisMock, $keyHelperMock);
        $stateResult = $redisCircuitBreaker->getState($serviceName);

        $this->assertEquals($expectedCircuitState, $stateResult);
    }

    /**
     * Data-provider for tests getting the current circuit state.
     *
     * @return array
     */
    public function dataProviderGetCircuitState()
    {
        return [
            [
                'expectedResult' => CircuitState::OPEN(),
                'isOpen' => true,
                'isHalfOpen' => false,
            ],
            [
                'expectedResult' => CircuitState::HALF_OPEN(),
                'isOpen' => false,
                'isHalfOpen' => true,
            ],
            [
                'expectedResult' => CircuitState::CLOSED(),
                'isOpen' => false,
                'isHalfOpen' => false,
            ]
        ];
    }
}
