<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Redis;
use Mockery;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
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
        $mutexMockery = \Mockery::mock('RZP\Services\Mutex', [$this->app]);

        $this->app->instance('api.mutex', $mutexMockery);

        $mutexMockery->shouldReceive('acquireAndRelease')
            ->andThrow(new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS));

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
        $mutexMockery = \Mockery::mock('RZP\Services\Mutex', [$this->app]);

        $this->app->instance('api.mutex', $mutexMockery);

        $mutexMockery->shouldReceive('acquireAndRelease')
            ->andThrow(new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS));

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
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);
    }

    public function testMutexCaptureRequestWithDiffRedisResponse()
    {
        $this->requestId = '';

        $payment = $this->defaultAuthPayment();

        $this->requestId = NULL;

        $this->capturePayment($payment['id'], $payment['amount']);
    }
}
