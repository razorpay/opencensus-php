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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MutexTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testMutexAcquiredCaptureRequest()
    {
        $payment = $this->defaultAuthPayment();

        Redis::shouldReceive('set')
            ->once()
            ->andReturn(null);

        Redis::shouldReceive('get')
                ->once()
                ->andReturnUsing(function()
                {
                    return null;
                });

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
        Redis::shouldReceive('set')
                ->once()
                ->andReturn(null);

        Redis::shouldReceive('get')
                ->once()
                ->andReturnUsing(function()
                {
                    return null;
                });

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

        Redis::shouldReceive('set')
                ->once()
                ->andReturnUsing(function()
                {
                    throw new \Predis\Response\ServerException('Internal Error');
                });

        Redis::shouldReceive('get')
                ->once()
                ->andReturnUsing(function()
                {
                    return 'false_id';
                });

        $this->capturePayment($payment['id'], $payment['amount']);
    }

    public function testMutexCaptureRequestWithDiffRedisResponse()
    {
        $payment = $this->defaultAuthPayment();

        Redis::shouldReceive('set')
                ->once()
                ->andReturnUsing(function ($resource, $requestId)
                    {
                        $this->requestId = $requestId;

                        return \Predis\Response\Status::get('QUEUED');
                    });

        Redis::shouldReceive('get')
                ->once()
                ->andReturnUsing(function()
                {
                    return $this->requestId;
                });

        Redis::shouldReceive('del')
                ->once()
                ->andReturn(true);

        $this->capturePayment($payment['id'], $payment['amount']);
    }
}
