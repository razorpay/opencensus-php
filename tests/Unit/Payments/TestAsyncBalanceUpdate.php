<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RZP\Constants\Environment;
use RZP\Mail\System\Trace;
use RZP\Models\DataStore\PrioritySet\Implementation\Redis;
use RZP\Models\Payment\Processor\Capture;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use RZP\Models\Transaction\Entity as TransactionEntity;

class TestAsyncBalanceUpdate extends TestCase {
    protected $traceMock;
    protected $paymentMock;
    protected $txnMock;
    protected $captureMock;
    protected $redisMock;
    protected $repoMock;
    protected $merchantMock;
    protected $mode;

    protected function setUp(): void {
        // Create mock for Redis client
        $this->redisMock = $this->createMock(Redis::class);

        // Create mock for trace logging
        $this->traceMock = $this->getMockBuilder(\Razorpay\Trace\Logger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info', 'critical'])
            ->getMock();
        $reflection = new \ReflectionClass($this->traceMock);
        $property = $reflection->getProperty('env');
        $property->setValue($this->traceMock, 'test');

        // Create mock for payment and transaction entities
        $this->paymentMock = $this->createMock(\RZP\Models\Payment\Entity::class);
        $this->txnMock = $this->createMock(\RZP\Models\Transaction\Entity::class);

        // Create mock for the repository
        $this->repoMock = $this->createMock(\RZP\Models\Base\Repository::class);

        // Create mock for merchant
        $this->merchantMock = $this->createMock(\RZP\Models\Merchant\Entity::class);
        $this->paymentMock->method('__get')
            ->with('merchant')
            ->willReturn($this->merchantMock);

        // Set up the mock for isFeatureEnabled for specific features
        $this->merchantMock->method('isFeatureEnabled')
            ->willReturnMap([
                [\RZP\Models\Feature\Constants::ASYNC_BALANCE_UPDATE, true],
                [\RZP\Models\Feature\Constants::ASYNC_TXN_FILL_DETAILS, false],
                [\RZP\Models\Feature\Constants::PG_LEDGER_REVERSE_SHADOW, false]
            ]);
        $this->txnMock->method('isBalanceUpdated')->willReturn(false);

        $this->appMock = [
            'env' => Environment::PRODUCTION,
        ];

        // Create mock for the TemporaryCaptureClass
        $this->captureMock = $this->getMockBuilder(TemporaryCaptureClass::class)
            ->setConstructorArgs([$this->redisMock, $this->traceMock, $this->repoMock, $this->appMock])
            ->onlyMethods(['isHandleAsyncBalanceUpdateByRedisQueueEnabled', 'getQueueNumberFromRedis', 'pushedToMerchantsBasedBalanceUpdateQueue'])
            ->getMock();
    }

    public function testHandleAsyncUpdateBalanceIfApplicableFetchesQueueFromRedis() {
        $merchantId = 'merchant_123';

        // Set up mocks for queue-related logic
        $this->paymentMock->method('getMerchantId')->willReturn($merchantId);
        $this->captureMock->method('pushedToMerchantsBasedBalanceUpdateQueue')->willReturn(false);
        $this->captureMock->method('getQueueNumberFromRedis')
            ->with($merchantId)
            ->willReturn(3);

        $this->captureMock->method('isHandleAsyncBalanceUpdateByRedisQueueEnabled')
            ->with($merchantId)
            ->willReturn(true);

        // Expect trace info logging
        $this->traceMock->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo(TraceCode::MERCHANT_BALANCE_UPDATE_INIT),
                $this->arrayHasKey('input')
            );

        // Call the method under test
        $this->captureMock->handleAsyncUpdateBalanceIfApplicable($this->paymentMock, $this->txnMock);

    }

    // More tests can be added for other scenarios, e.g., checking different feature flags, exception handling, etc.
}

class TemporaryCaptureClass {
    use \RZP\Models\Payment\Processor\Capture;

    public \RZP\Models\Base\Repository $repo;

    public function __construct($redis, $trace, \RZP\Models\Base\Repository $repo, $appMock) {
        $this->redis = $redis;
        $this->trace = $trace;
        $this->repo = $repo;
        $this->app = $appMock;
        $this->mode = \RZP\Constants\Mode::LIVE;
    }
}
