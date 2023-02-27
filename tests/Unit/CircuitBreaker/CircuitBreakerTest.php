<?php

namespace Unit\CircuitBreaker;

use RZP\Services\CircuitBreaker\Constant;
use RZP\Services\CircuitBreaker\CircuitState;
use RZP\Services\CircuitBreaker\CircuitBreakerClient;
use RZP\Services\CircuitBreaker\Store\CircuitBreakerStore;
use RZP\Services\CircuitBreaker\Store\CircuitBreakerRedisStore;
use RZP\Exception\CircuitException;
use Mockery\Mock;

/**
 * Class CircuitBreakerTest
 *
 * @package Tests\Unit
 *
 */
class CircuitBreakerTest extends \RZP\Tests\TestCase
{
    protected function setUp(): void
    {
      $this->markTestSkipped();
    }

    public function testExceptionWithServiceName()
    {
        $serviceName = 'TEST_SERVICE_NAME';
        $exception = new CircuitException($serviceName);
        $this->assertEquals($serviceName, $exception->getServiceName());
    }

    /**
     * Test success calling a service.
     */
    public function testWhenCallToAServiceWasSucceed()
    {
        $serviceName = 'SERVICE';

        $circuitBreakerDriverMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerDriverMock->shouldReceive('closeCircuit')
                                 ->once()
                                 ->with($serviceName)
                                 ->andReturnTrue();

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerDriverMock);
        $this->assertNull($circuitBreaker->succeed($serviceName));
    }

    /**
     * Test if the service can be callend (half-open, closed) or can't (open).
     * (without exceptions.)
     *
     * @param string $currentState
     * @param bool $canPass
     *
     * @dataProvider dataProviderTestCanPassTrue
     *
     * @return void
     * @throws \Exception
     */
    public function testCanPassWithoutExceptions(string $currentState, bool $canPass)
    {
        $serviceName = 'SERVICE_NAME_TEST';

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn($currentState);

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock,[
            Constant::EXCEPTIONS_ON => false]);
        $result = $circuitBreaker->canPass($serviceName);

        $this->assertEquals($canPass, $result);
    }

    /**
     * Data provider for the test can pass.
     */
    public function dataProviderTestCanPassTrue()
    {
        return [
            [
                'state' => (new CircuitState())->CLOSED(),
                'canPass' => true
            ],
            [
                'state' => (new CircuitState())->HALF_OPEN(),
                'canPass' => true
            ],
            [
                'state' => (new CircuitState())->OPEN(),
                'canPass' => false
            ]
        ];
    }

    /**
     * Test if the service can be callend (half-open, closed) or can't (open).
     * (WITH EXCEPTION.)
     *
     * @dataProvider dataProviderTestCanPassTrue
     *
     * @return void
     * @throws \Exception
     */
    public function testCantPassWithExceptions()
    {
        $this->expectException(CircuitException::class);
        $this->expectExceptionMessage('The circuit is open.');
        $serviceName = 'SERVICE_NAME_TEST';

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn((new CircuitState())->OPEN());

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock, ['exceptions_on' => true]);
        $circuitBreaker->canPass($serviceName);
    }

    /**
     * Test when incrementing the total of failures AND the circuit is not half-open
     *      AND the total of failures is less than the limit.
     */
    public function testServiceFailureWhenTheCircuitIsNotHalfOpenAndTotalFailuresIsLessThanTheLimit()
    {
        $serviceName = 'SERVICE_NAME_TEST';
        $timeWindow = 123;

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);

        $circuitBreakerAdapterMock->shouldReceive('addFailure')
                                  ->once()
                                  ->with($serviceName, $timeWindow);

        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn((new CircuitState())->OPEN());

        $circuitBreakerAdapterMock->shouldReceive('getTotalFailures')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn(0);

        $circuitBreakerAdapterMock->shouldNotReceive('openCircuit');

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock, ['time_window' => $timeWindow]);
        $this->assertNull($circuitBreaker->failed($serviceName));
    }

    /**
     * Test when incrementing the total of failures AND the circuit is Half-open.
     */
    public function testServiceFailureWhenTheCircuitIsHalfOpen()
    {
        $serviceName = 'SERVICE_NAME_TEST';
        $timeWindow = 123;

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerAdapterMock->shouldReceive('addFailure')
                                  ->once()
                                  ->with($serviceName, $timeWindow);

        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn((new CircuitState())->HALF_OPEN());

        $circuitBreakerAdapterMock->shouldReceive('getTotalFailures')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn(0);

        $defaultSettingTimeOutOpen = 30;
        $defaultSettingTimeOutHalfOpen = 20;

        $circuitBreakerAdapterMock->shouldReceive('openCircuit')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen);

        $circuitBreakerAdapterMock->shouldReceive('setCircuitHalfOpen')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen + $defaultSettingTimeOutHalfOpen);

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock, ['time_window' => $timeWindow]);
        $this->assertNull($circuitBreaker->failed($serviceName));
    }

    /**
     * Test when increasing the total of failures for a service AND the circuit is closed, however
     * the total of failures reaches its limit.
     */
    public function testServiceFailureWhenTheCircuitIsClosedButTheNumberOfFailuresIsHigherThanTheLimit()
    {
        $serviceName = 'SERVICE_NAME_TEST';
        $timeWindow = 123;

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerAdapterMock->shouldReceive('addFailure')
                                  ->once()
                                  ->with($serviceName, $timeWindow);

        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn((new CircuitState())->CLOSED());

        $circuitBreakerAdapterMock->shouldReceive('getTotalFailures')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn(51);

        $defaultSettingTimeOutOpen = 30;
        $defaultSettingTimeOutHalfOpen = 20;

        $circuitBreakerAdapterMock->shouldReceive('openCircuit')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen);

        $circuitBreakerAdapterMock->shouldReceive('setCircuitHalfOpen')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen + $defaultSettingTimeOutHalfOpen);

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock, ['time_window' => $timeWindow]);
        $this->assertNull($circuitBreaker->failed($serviceName));
    }

    /**
     * Test when increasing the total of failures for a service AND the circuit is closed, however
     * the total of failures reaches its limit and there is an {@see Alert} object to emmit a message.
     */
    public function testServiceFailureWhenTheCircuitIsClosedButTheNumberOfFailuresIsHigherThanTheLimitAndEmmitAMessage()
    {
        $serviceName = 'SERVICE_NAME_TEST';
        $timeWindow = 123;

        $circuitBreakerAdapterMock = \Mockery::mock(CircuitBreakerStore::class);
        $circuitBreakerAdapterMock->shouldReceive('addFailure')
                                  ->once()
                                  ->with($serviceName, $timeWindow);

        $circuitBreakerAdapterMock->shouldReceive('getState')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn((new CircuitState())->CLOSED());

        $circuitBreakerAdapterMock->shouldReceive('getTotalFailures')
                                  ->once()
                                  ->with($serviceName)
                                  ->andReturn(51);

        $defaultSettingTimeOutOpen = 30;
        $defaultSettingTimeOutHalfOpen = 20;

        $circuitBreakerAdapterMock->shouldReceive('openCircuit')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen);

        $circuitBreakerAdapterMock->shouldReceive('setCircuitHalfOpen')
                                  ->once()
                                  ->with($serviceName, $defaultSettingTimeOutOpen + $defaultSettingTimeOutHalfOpen);

        $circuitBreaker = new CircuitBreakerClient($circuitBreakerAdapterMock, ['time_window' => $timeWindow]);
        $this->assertNull($circuitBreaker->failed($serviceName));
    }
}
