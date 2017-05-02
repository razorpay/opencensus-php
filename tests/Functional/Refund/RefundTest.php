<?php

namespace RZP\Tests\Functional\Refund;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
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
        $this->testDataFilePath = __DIR__ . '/helpers/RefundTestData.php';

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

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
    }

    public function testRefundDirect()
    {
        $payment = $this->fixtures->create('payment:captured');

        $refund = $this->refund(
            [
                'payment_id' => $payment->getPublicId(),
                'notes'      => ['a' => 'b'],
            ]);

        $this->assertEquals('refund', $refund['entity']);
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

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refunds = $this->getEntities('refund', ['payment_id' => $payment['id']]);
        $this->assertEquals($refunds['count'], 4);
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

    public function testRefundWithFloatAmountString()
    {
        $this->startTest($this->payment['public_id'], '100.1');
    }

    public function testRefundWithFloatAmount()
    {
        $this->startTest($this->payment['public_id'], 100.1);
    }

    public function testRefundOfOldAuthorizedPayments()
    {
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(6)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

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

    public function testRefundPaymentsWithRefundDelay()
    {
        // Change auto refund delay to 2 days
        $this->fixtures->merchant->editAutoRefundDelay('2 days');

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(2)->timestamp;

        $payments = $this->fixtures->times(3)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(6)->timestamp;
        $this->fixtures->on('test')->create('balance', ['id' => '1MercShareTerm', 'balance' => '1000000']);

        $payment = $this->fixtures->create(
            'payment:authorized',
            ['created_at' => $createdAt, 'merchant_id' => '1MercShareTerm', 'transaction_id' => null]);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(4, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(4, $content['authorized']);
    }

    public function testRefundCalledOnPurchaseWithoutCapture()
    {
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(6)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:purchased',
            ['created_at' => $createdAt]);

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
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(6)->timestamp;
        $authorizedAt = Carbon::today('Asia/Kolkata')->timestamp;

        $payment = $this->fixtures->create(
            'payment:captured',
            ['authorized_at' => $authorizedAt, 'created_at' => $createdAt]);

        $this->fixtures->payment->edit($payment->getId(), ['status' => 'authorized']);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        $hdfcRefundedEntity = $this->getLastEntity('hdfc', true);

        $this->assertEquals('refunded', $hdfcRefundedEntity['status']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertNotNull($refund['transaction_id']);
    }

    public function testVerifyRefund()
    {
        // Case 1
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund2 = $this->refundPayment($payment['id'], $payment['amount']);

        $refund2 = $this->getLastEntity('refund', true);

        $response = $this->verifyRefund($refund2['id']);

        $this->assertEquals('Refund verified successfully.', $response[0]['verify_refund']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertArraySelectiveEquals($refund2, $refund);
    }

    public function testVerifyBuggyRefund()
    {
        // Case where refunded payment has no entry in hdfc

        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:purchased',
            [
                'authorized_at' => $authorizedAt,
                'created_at' => $authorizedAt
            ]);

        $content = $this->refundOldAuthorizedPayments();

        $refund = $this->getLastEntity('refund', true);

        $hdfcEntityForRefund = $this->getLastEntity('hdfc', true);

        $refundTransaction = $this->getLastEntity('transaction', true);

        // Disable foreign key checks to allow testing buggy case
        DB::statement("SET foreign_key_checks = 0");

        $this->fixtures->hdfc->edit($hdfcEntityForRefund['id'], ['payment_id' => 'random_id', 'refund_id' => 'random_id']);

        // Enable foreign key checks
        DB::statement("SET foreign_key_checks = 1");

        $this->fixtures->refund->edit($refund['id'], ['transaction_id' => null, 'gateway_refunded' => false]);
        $this->fixtures->transaction->edit($refundTransaction['id'], ['entity_id' => 'boohooboohooaa']);

        $response = $this->verifyRefund($refund['id']);

        $hdfcEntityForRefund = $this->getLastEntity('hdfc', true);
        $refund = $this->getLastEntity('refund', true);
        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($refund['id'], $refundTransaction['entity_id']);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('txn_' . $refund['transaction_id'], $refundTransaction['id']);

        $this->assertEquals('Refund verification failed and Refund performed.', $response[0]['verify_refund']);

        $this->assertEquals('refunded', $hdfcEntityForRefund['status']);

        $this->assertEquals($payment['id'], $hdfcEntityForRefund['payment_id']);

        $this->assertEquals($refund['id'], 'rfnd_' . $hdfcEntityForRefund['refund_id']);
    }

    public function testCreateMissingRefundTransaction()
    {
        $this->markTestSkipped('Transactions are getting created now');

        $authorizedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:purchased',
            [
                'authorized_at' => $authorizedAt,
                'created_at' => $authorizedAt
            ]);

        $content = $this->refundOldAuthorizedPayments();

        $refund = $this->getLastEntity('refund', true);

        $hdfcEntityForRefund = $this->getLastEntity('hdfc', true);

        $this->assertEquals($refund['id'], 'rfnd_' . $hdfcEntityForRefund['refund_id']);

        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        $this->fixtures->refund->edit($refund['id'], ['transaction_id' => null]);
        $this->fixtures->transaction->edit($refundTransaction['id'], ['entity_id' => 'boohooboohooaa']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        $refund = $this->getLastEntity('refund', true);

        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        $this->assertNotNull($refundTransaction['id'], $refund['transaction_id']);

        $this->assertEquals($refund['id'], $refundTransaction['entity_id']);
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


    public function testRefundValidationOnWrongGateway()
    {
        $this->ba->appAuth();

        parent::startTest();
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
                            'payment'   => [
                                'amount' => 'INR 500.00'
                            ],
                            'merchant' => [],
                            'customer'  => [
                                'email' => 'a@b.com',
                                'phone' => '9918899029'
                            ],
                            'refund'  => [
                                'amount' => 'INR 500.00'
                            ]
                        );
                        $this->assertArraySelectiveEquals($testData, $data);

                        return true;
                    }),
                Mockery::any());
    }
}
