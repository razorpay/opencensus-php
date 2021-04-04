<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Redis;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Tests for refund payments
 *
 * For refund payments, first we need to create a
 * captured payment. By default, an captured payment entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So refund tests which supposedly hit hdfc gateway for refund,
 * should first call for a normal hdfc authorized + captured payment
 * instead of utilizing the default created payment entity.
 */

class MutexTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MutexTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testMutexAcquiredCaptureRequest()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get', 'setex'])
                          ->getMock();

        Redis::shouldReceive('connection')
             ->andReturn($redisMock);

        $redisMock->method('set')
                  ->will($this->returnValue(null));

        $redisMock->method('get')
                  ->will($this->returnValue(null));

        $payment = $this->defaultAuthPayment();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->capturePayment($payment['id'], $payment['amount']);
        });

        $paymentEntity = $this->getEntityById('payment', $payment['id'], true);

        $this->assertSame('authorized', $paymentEntity['status']);
    }

    public function testMutexAcquiredRefundRequest()
    {
        $redisMock   = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set'])->getMock();

        Redis::shouldReceive('connection')
               ->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue(null));

        $redisMock->method('set')
                  ->will($this->returnValue(null));


        $payment = $this->fixtures->create('payment:captured');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->refundPayment($payment->getPublicId());
        });
    }

    public function testCaptureRequest()
    {
        $payment = $this->defaultAuthPayment();

        $expected = $this->testData[__FUNCTION__];

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->assertArraySelectiveEquals($expected, $capturedPayment);
    }

    public function testCaptureRequestWithException()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set', 'setex', 'del'])
                          ->getMock();

        Redis::shouldReceive('connection')
             ->andReturn($redisMock);

        $redisMock->expects($this->exactly(1))
                  ->method('set')
                  ->willThrowException(new \Predis\Response\ServerException('Internal Error'));

        $redisMock->method('get')->will($this->returnValue(null));


        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);
    }

    public function testMutexCaptureRequestWithDiffRedisResponse()
    {
        $this->requestId = '';

        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get', 'set','del', 'setex'])
                          ->getMock();

        Redis::shouldReceive('connection')
               ->andReturn($redisMock);

        $redisMock->expects($this->exactly(1))
                  ->method('set')->will($this->returnCallback(
                        function ($resourceId, $requestId)
                        {
                            $this->requestId = $requestId;

                            return \Predis\Response\Status::get('QUEUED');
                        }));

        $redisMock->method('del')->will($this->returnValue(true));

        $redisMock->method('get')->will($this->returnValue($this->requestId));


        $payment = $this->defaultAuthPayment();

        $this->requestId = NULL;

        $this->capturePayment($payment['id'], $payment['amount']);
    }
}
