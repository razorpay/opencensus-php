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

class RefundTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/refund.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();
    }

    public function testRefund()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest();
//        $this->mockRefundEmail();

        $refund = $this->startTest($payment['id'], (string)$payment['amount']);

        $this->assertEquals(substr($refund['id'], 0, 5), 'rfnd_');

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
    }

    public function testMultipleRefunds()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest(4);

        $this->refundPayment($payment['id'], '10000');
        $this->refundPayment($payment['id'], '20000');
        $this->refundPayment($payment['id'], '12000');
        $this->refundPayment($payment['id'], '8000');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$payment['id'];

        return $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundWithHigherAmount()
    {
        $this->startTest($this->payment['public_id'], 1000001);
    }

    public function testMultipleRefundsWithHigherAmount()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id'], 20000);

        $this->startTest($payment['id'], 30000);
    }

    public function testRefundOnRefundedPayment()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id']);

        $this->startTest($payment['id'], 100);
    }

    public function testRefundByMerchantOnAuthorizedPayment()
    {
        $payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->startTest($payment['id']);
    }

    public function testRefundWithNegativeAmount()
    {
        $this->startTest($this->payment['public_id'], -1);
    }

    public function testRefundWithZeroAmount()
    {
        $this->startTest($this->payment['public_id'], 0);
    }

    public function testRefundWithBlankAmount()
    {
        $this->startTest($this->payment['public_id'], '');
    }

    public function testRefundWithSpacedAmount()
    {
        $this->startTest($this->payment['public_id'], ' 100');
    }

    public function testRefundofOldAuthorizedPayments()
    {
        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['authorized_at' => $authorizedAt, 'created_at' => $authorizedAt]);

        $payments = $this->fixtures->times(2)->create('payment:authorized');

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);
    }

    public function testRefundOfMultipleAuthorizedPaymentsForOrder()
    {
        $this->ba->appAuth();
        $orders = $this->fixtures->times(2)->create('order');

        $orderIdOne = $orders[0]->getId();
        $orderIdTwo = $orders[1]->getId();

        // Card not getting created properly when using ->times(x)
        $this->fixtures->payment->createAuthorized(['order_id' => $orderIdOne]);
        $this->fixtures->payment->createAuthorized(['order_id' => $orderIdOne]);

        $this->fixtures->payment->createAuthorized(['order_id' => $orderIdTwo]);
        $this->fixtures->payment->createAuthorized(['order_id' => $orderIdTwo]);
        $this->fixtures->payment->createCaptured(['order_id' => $orderIdTwo]);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    public function testRefundCreateOnGatewayForMissingRefunds()
    {
        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    public function testRefundCalledOnPurchaseWithoutCapture()
    {
        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:purchased',
            ['authorized_at' => $authorizedAt, 'created_at' => $authorizedAt]);

        $payments = $this->fixtures->times(2)->create('payment:purchased');

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);

        $refundedEntities = $this->getEntities('hdfc', ['count' => 2], true);

        foreach ($refundedEntities['items'] as $entity)
        {
            $this->assertEquals('refunded', $entity['status']);
        }

    }

    // Testing Buggy Case where a payment is captured in hdfc gateway
    // But is in authorised state in RZP db.
    // This will also be picked up for a refund and refunded.
    public function testRefundOnHdfcCapturedPaymentAuthorized()
    {
        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:captured',
            ['authorized_at' => $authorizedAt, 'created_at' => $authorizedAt]);

        $this->fixtures->payment->edit($payment->getId(), ['status' => 'authorized']);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        $refunded = $this->getLastEntity('hdfc', true);

        $this->assertEquals('refunded', $refunded['status']);
    }

    public function testVerifyRefund()
    {
        // Case 1
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund2 = $this->refundPayment($payment['id'], $payment['amount']);

        $response = $this->verifyRefund($refund2['id']);

        $this->assertEquals('Refund verified successfully.', $response[0]['verify_refund']);
    }

    public function testVerifyBuggyRefund()
    {
        $this->markTestIncomplete();

        // Case where refunded payment has no entry in hdfc
        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:purchased',
            ['authorized_at' => $authorizedAt, 'created_at' => $authorizedAt]);

        $hdfcEntityForPayment = $this->getEntities('hdfc', ['from'=>$authorizedAt -1, 'to'=>$authorizedAt+1],true);

        $content = $this->refundOldAuthorizedPayments();

        $refund = $this->getLastEntity('refund', true);

        $hdfcEntityForRefund = $this->getLastEntity('hdfc', true);

        // Disable foreign key checks to allow testing buggy case
        DB::statement("SET foreign_key_checks = 0");

        $this->fixtures->hdfc->edit($hdfcEntityForRefund['id'], ['payment_id' => 'random_id', 'refund_id' => 'random_id']);

        // Enable foreign key checks
        DB::statement("SET foreign_key_checks = 1");

        $response = $this->verifyRefund($refund['id']);

        $this->assertEquals('Refund verification failed and Refund performed.', $response['verify_refund']);

        $refunded = $this->getLastEntity('hdfc', true);

        $this->assertEquals('refunded', $refunded['status']);

        $this->assertEquals($payment['id'], $refunded['payment_id']);

        $this->assertNotEquals($refund['id'], $refunded['refund_id']);
    }

    public function testFetchRefundById()
    {
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $refund = $this->getEntityById('refund', $rfnd['public_id']);
        $this->assertArraySelectiveEquals($rfnd->toArrayPublic(), $refund);

        $refunds = $this->getEntities('refund');
        $rfnds = ['entity' => 'collection', 'count' => 1, 'items' => [$rfnd->toArrayPublic()]];
        $this->assertArraySelectiveEquals($rfnds, $refunds);
    }

    public function testFetchRefunds()
    {
        $this->ba->privateAuth();
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $paymentId = $payment['public_id'];

        $content = $this->fetchRefundsForPayment($paymentId);
        $testData = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'entity' => 'refund',
                    'currency' => 'INR',
                ]
            ]
        ];
    }

    public function startTest($paymentId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $paymentId, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        $url = '/payments/'.$id.'/refund';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function mockDashboardRequest($times = 1)
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('RZP\Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('refund', Mockery::type('RZP\Models\\Base\\PublicEntity'));
    }

    protected function mockRefundEmail($times = 1)
    {
        \Mail::shouldReceive('queue')
            ->twice()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data)
                    {
                        $testData = array(
                            'payment'   =>  [
                                'amount'=>  'INR 500.00'
                            ],
                            'merchant'  =>  [],
                            'customer'   =>  [
                                'email' =>  'a@b.com',
                                'phone' => '9918899029'
                            ],
                            'refund'  =>  [
                                'amount' => 'INR 500.00'
                            ]
                        );
                        $this->assertArraySelectiveEquals($testData, $data);

                        return true;
                    }),
                Mockery::any());
    }
}
