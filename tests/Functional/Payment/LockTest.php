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

class LockTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/lockTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testLockAcquiredCaptureRequest()
    {
        $payment = $this->defaultAuthPayment();

        Redis::shouldReceive('set')
            ->once()
            ->andReturn(null);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->capturePayment($payment['id'], $payment['amount']);
        });
    }

    public function testLockAcquiredRefundRequest()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        Redis::shouldReceive('set')
                ->once()
                ->andReturn(null);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->refundPayment($payment['id']);
        });
    }

    public function testCaptureRequest()
    {
        $payment = $this->defaultAuthPayment();

        $expected = $this->testData[__FUNCTION__];

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->assertArraySelectiveEquals($expected, $capturedPayment);
    }

    public function testLockCaptureRequestWithDiffRedisResponse()
    {
        $payment = $this->defaultAuthPayment();

        Redis::shouldReceive('set')
                ->once()
                ->andReturn(\Predis\Response\Status::get('QUEUED'));

        $this->capturePayment($payment['id'], $payment['amount']);
    }
}
