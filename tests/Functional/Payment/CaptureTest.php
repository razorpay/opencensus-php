<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mockery;
use Mail;

use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Dashboard\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor;
use RZP\Models\Payment as Payments;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Tests for capture payments
 *
 * For capture payments, first we need to create an
 * authorized payment. By default, an authorized payment entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So capture tests which supposedly hit hdfc gateway for capture,
 * should first call for a normal hdfc authorized payment instead
 * of utilizing the default created payment entity.
 */

class CaptureTest extends TestCase
{
    use PaymentTrait;

    protected $testData = null;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CaptureTestData.php';

        parent::setUp();

        $payment = $this->fixtures->create('payment:authorized');
        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
    }

    public function testCapture()
    {
        Mail::fake();

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->mockDashboardRequest();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertSent(CapturedMail::class);
    }

    public function testBulkCapture()
    {
        Mail::fake();

        $count = 3;

        $payments = [];

        for ($i=0; $i < $count; $i++) {
            $payments[] = $this->defaultAuthPayment();
        }

        $this->ba->appAuth();

        $this->mockDashboardRequest($count);

        $this->startBulkTest($payments);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertSent(CapturedMail::class);
    }

    public function testCaptureWithFeeBreakupException()
    {
        $payment = $this->fixtures->create('payment:card_authorized');

        $merchant = $this->getLastEntity('merchant', true);

        $merchant['id'] = $payment['merchant_id'];

        $merchantEntity = (new Merchant\Entity)->fill($merchant);

        $class = Payments\Processor\Processor::class;

        $processor = Mockery::mock($class, [$merchantEntity])
                        ->makePartial();

        $processor->shouldReceive('saveFeeDetails')
            ->times(1)
            ->withAnyArgs()
            ->andThrow(new Exception\LogicException(
                    'Error while recording fee breakup',
                    ErrorCode::BAD_REQUEST_FEE_BREAKUP_CREATION_FAILED));

        $params = ['amount' => 1000000];

        try
        {
            $processor->capture($payment, $params);
        }
        catch (Exception\LogicException $ex)
        {
            $this->assertEquals("BAD_REQUEST_FEE_BREAKUP_CREATION_FAILED", $ex->getCode());

            $this->assertEquals("Error while recording fee breakup", $ex->getMessage());

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals('authorized', $payment['status']);

            $this->assertNull($payment['captured_at']);
            return;
        }

        $this->fail();
    }

    public function testCaptureTwice()
    {
        $payment = $this->fixtures->create('payment:captured')->toArrayPublic();

        $this->payment = $payment;

        $this->startTest();
    }

    public function testCaptureWithGatewayCapturedTrue()
    {
        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 976400);
        $this->assertEquals($transaction['fee'], 23600);
        $this->assertEquals($transaction['tax'], 3600);
        $this->assertEquals($transaction['credit_type'], 'default');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    public function testCaptureWithDifferentAmount()
    {
        $amount = $this->payment['amount'] - 1000;

        $this->startTest(null, $amount);
    }

    // public function testCaptureWithLessAmountThanAuth()
    // {
    //     $amount = 10000;

    //     $this->payment = $this->defaultAuthPayment();

    //     $this->ba->privateAuth();

    //     $this->startTest(null, $amount);
    // }

    // public function testCaptureWithMoreAmountThanAuth()
    // {
    //     $amount = $this->payment['amount'] + 1000;

    //     $this->startTest(null, $amount);
    // }

    // public function testCaptureWithNoAmount()
    // {
    //     unset($this->payment['amount']);

    //     $this->startTest();
    // }

    // public function testCaptureWithZeroAmount()
    // {
    //     $this->payment['amount'] = 0;

    //     $this->startTest();
    // }

    // public function testCaptureWithMinAmountAllowedMinusOne()
    // {
    //     //
    //     // Minium amount allowed for capture
    //     //
    //     $this->payment['amount'] = 99;

    //     $this->startTest();
    // }

    // public function testCaptureWithMinAmountAllowed()
    // {
    //     $this->payment = $this->defaultAuthPayment();

    //     $this->payment['amount'] = 100;

    //     $this->ba->privateAuth();

    //     $this->startTest();
    // }

    // public function testCaptureWithOverflowingAmount()
    // {
    //     $this->payment['amount'] = 100000000000000000000000000000000000;

    //     $this->startTest();
    // }

    // public function testCaptureWithNegativeAmount()
    // {
    //     $this->payment['amount'] = -10000;

    //     $this->startTest();
    // }

    public function testCaptureWithRandomId()
    {
        $this->payment['id'] = '2fe34ae575104c0a95c3';

        $this->startTest();
    }

    public function testAutoCaptureOnLateAuthorizedPaymentWithDefaultAutoRefund()
    {
        $payment = $this->createFailedPayment(1, false);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);

        $this->assertTrue($payment['amount'] === $order['amount']);
    }

    public function testAutoCaptureOnLateAuthPaymentWithDefaultAutoRefundAndConfigSet()
    {
        $payment = $this->createFailedPayment(1, false);

        $this->authorizeFailedPayment($payment['id']);

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_capture_late_auth' => true,
            ]);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);

        $this->assertTrue($payment['amount'] === $order['amount']);
    }

    public function testAutoCaptureOnLateAuthPaymentWithAutoRefundAndConfigNotSet()
    {
        $payment = $this->createFailedPayment(1, false);

        $this->authorizeFailedPayment($payment['id']);

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay' => '2 days'
            ]);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);

        $this->assertTrue($payment['amount'] === $order['amount']);
    }

    public function testAutoCaptureOnLateAuthorizedPaymentWithAutoRefund()
    {
        $payment = $this->createFailedPayment('1', false);

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay'      => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paid', $order['status']);

        $this->assertTrue($payment['amount'] === $order['amount']);
    }

    public function testAutoCaptureFailAsPastMerchantRefundTimePeriod()
    {
        $payment = $this->createFailedPayment('1', false);

        $past = Carbon::today(Timezone::IST)->subDays(1)->timestamp;
        $this->fixtures->payment->edit($payment['id'], ['created_at' => $past]);

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay'      => '2 hours',
                'auto_capture_late_auth' => true,
            ]);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);
    }

    public function testAutoCaptureInvoiceOnLateAuthorizedPayment()
    {
        $payment = $this->createFailedPayment();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paid', $order['status']);
        $this->assertEquals('paid', $invoice['status']);

        $this->assertTrue($payment['amount'] === $order['amount']);
    }

    public function testInvoiceAutoCaptureFailAsPastDefaultRefundTimePeriod()
    {
        $payment = $this->createFailedPayment();

        $past = Carbon::today(Timezone::IST)->subDays(6)->timestamp;
        $this->fixtures->payment->edit($payment['id'], ['created_at' => $past]);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals('issued', $invoice['status']);
    }

    public function testInvoiceAutoCaptureFailAsPastMerchantRefundTimePeriod()
    {
        $payment = $this->createFailedPayment();

        $past = Carbon::today(Timezone::IST)->subDays(1)->timestamp;
        $this->fixtures->payment->edit($payment['id'], ['created_at' => $past]);

        $defaultMerchantId = '10000000000000';

        $this->fixtures->merchant->edit($defaultMerchantId, ['auto_refund_delay' => '2 hours']);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals('issued', $invoice['status']);
    }

    public function testAutoCaptureFailAsInvoiceExpired()
    {
        $payment = $this->createFailedPayment();

        $invoice = $this->getLastEntity('invoice', true);

        $past = Carbon::today(Timezone::IST)->subDays(1)->timestamp;
        $invoice = $this->fixtures->invoice->edit($invoice['id'], ['status' => 'expired']);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);
    }

    public function testInvoicePaymentNotAutoCapturedWithOrder()
    {
        $payment = $this->createFailedPayment('0');

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(true, $order['authorized']);
        $this->assertEquals('issued', $invoice['status']);
    }

    public function testMultiplePaymentsWithAutoCaptureForInvoice()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $order = $this->fixtures->create('order', [
            'id'              => '100000000order',
            'payment_capture' => '1'
            ]);

        $dueBy = Carbon::now(Timezone::IST)->addDays(10)->timestamp;

        $this->fixtures->create(
                            'invoice',
                            [
                                'due_by' => $dueBy,
                                'amount' => 1000000,
                            ]);

        $this->gateway = 'hdfc';

        $this->mockServerVerifyContentFunction();

        $this->gateway = null;

        $this->doAuthPaymentAndCatchException($order);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertInternalErrorCode($payment1, 'GATEWAY_ERROR_UNKNOWN_ERROR');

        $this->doAuthPaymentAndCatchException($order);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertInternalErrorCode($payment2, 'GATEWAY_ERROR_UNKNOWN_ERROR');

        $this->authorizeFailedPayment($payment2['id']);
        $this->authorizeFailedPayment($payment1['id']);

        $olderPayment = $this->getEntityById('payment', $payment1['id'], true);
        $newerPayment = $this->getEntityById('payment', $payment2['id'], true);

        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('captured', $newerPayment['status']);
        $this->assertEquals('authorized', $olderPayment['status']);
        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(true, $order['authorized']);
        $this->assertEquals('paid', $invoice['status']);

        $this->assertTrue($olderPayment['amount'] === $order['amount']);
    }

    public function testCaptureAfterRefund()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id']);

        $this->payment = $payment;

        $this->startTest();
    }

    public function testAutoCapture()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);
        $this->app['config']->set('gateway.mock_atom', true);

        $createdAt = time() - rand(0, 23) * 60 * 60;
        $updatedAt = $createdAt;

        $payment = $this->fixtures->create(
            'payment:authorized', ['created_at' => $createdAt, 'updated_at' => $updatedAt]);

        $payment = $this->fixtures->create(
            'payment:netbanking_authorized', ['created_at' => $createdAt, 'updated_at' => $updatedAt]);

        $createdAt = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
        $updatedAt = $createdAt;

        $payment = $this->fixtures->create(
            'payment:status_created', ['created_at' => $createdAt, 'updated_at' => $updatedAt]);

        $payment = $this->fixtures->create(
            'payment:captured', ['created_at' => $createdAt, 'updated_at' => $updatedAt]);

        $payment = $this->fixtures->create(
            'payment:netbanking_captured', ['created_at' => $createdAt, 'updated_at' => $updatedAt]);

        $x = range(1,3);

        foreach ($x as $i)
        {
            $createdAt = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
            $updatedAt = $createdAt;

            $payment = $this->fixtures->create(
                'payment:authorized',
                ['created_at' => $createdAt,
                 'updated_at' => $updatedAt]);
        }

        foreach ($x as $i)
        {
            $createdAt = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
            $updatedAt = $createdAt;

            $payment = $this->fixtures->create(
                'payment:netbanking_authorized',
                ['created_at' => $createdAt,
                 'updated_at' => $updatedAt]);
        }

        $payment = $this->fixtures->create('payment:netbanking_authorized');

        $content = $this->doAutoCapture();

        $this->assertSame(6, $content['count']);
    }

    public function testAutoCaptureEmail()
    {
        $time = Carbon::today(Timezone::IST)->timestamp;
        $createdAt = $time - rand(0, 23) * 60 * 60;

        $attributes = [
            'authorized_at' => $createdAt + 1,
            'captured_at'   => $createdAt + 10,
            'created_at'    => $createdAt,
            'updated_at'    => $createdAt + 10
        ];

        // The following two payments have been captured but not auto-captured
        $payment = $this->fixtures->create(
            'payment:captured', $attributes);
        $payment = $this->fixtures->create(
            'payment:netbanking_captured', $attributes);

        $createdAt = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
        $attributes = [
            'authorized_at' => $createdAt + 1,
            'captured_at'   => $createdAt + 10,
            'created_at'    => $createdAt,
            'updated_at'    => $createdAt + 10
        ];

        $payment = $this->fixtures->create(
            'payment:status_created', $attributes);

        $payment = $this->fixtures->create(
            'payment:authorized', $attributes);

        $payment = $this->fixtures->create(
            'payment:netbanking_authorized', $attributes);

        $x = range(1,3);

        $merchant = $this->fixtures->create('merchant_fluid')->get();

        // Only the following 6 payments are actually auto-captured. The above rest is just noise
        foreach ($x as $i)
        {
            $createdAt = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
            $attributes = [
                'authorized_at' => $createdAt + 1,
                'captured_at'   => $createdAt + 10,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10,
                'auto_captured' => 1
            ];

            $payment = $this->fixtures->create(
                'payment:captured', $attributes);
        }

        foreach ($x as $i)
        {
            $createdAt = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
            $attributes = [
                'authorized_at' => $createdAt + 1,
                'captured_at'   => $createdAt + 10,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10,
                'auto_captured' => 1,
                'merchant_id'   => $merchant->getId()
            ];

            $payment = $this->fixtures->create(
                'payment:netbanking_captured', $attributes);
        }

        $payment = $this->fixtures->create('payment:netbanking_authorized');

        $mock = Mockery::mock('RZP\Services\Mailgun')->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('sendMessage')->times(2);
        $mock->shouldReceive('getMode')->andReturn('test');

        $this->app->instance('mailgun', $mock);

        $content = $this->sendAutoCaptureEmails();

        $this->assertSame(6, $content['payments_count']);
        $this->assertSame(2, $content['emails_count']);
    }

    // Fee Model = Prepaid
    // Fee Bearer = Platform
    // Fee Credit > 0
    public function testTransactionOnCaptureWithFeeCreditForPrepaid()
    {
        $this->fixtures->create('credits', [
            'type'  => 'fee',
            'value' => 14000,
        ]);

        $this->fixtures->create('credits', [
            'type'  => 'fee',
            'value' => 10000,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['fee_credits' => 24000]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 9600);
        $this->assertEquals($creditTransactions['items'][1]['credits_used'], 14000);

        //need to update the test case To fill

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 23600);
        $this->assertEquals($transaction['tax'], 3600);
        $this->assertEquals($transaction['fee_credits'], 23600);
        $this->assertEquals($transaction['credit_type'], 'fee');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    /**
     *  This is to make sure that credits
     *  are used first which are expiring first
     */
    public function testCreditTransactionWithFeeCreditForPrepaid()
    {
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 34000,
                       'expired_at' => time() + 2*24*60*60,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'fee',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest();

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 13600);
        $this->assertEquals($creditTransactions['items'][0]['credits_id'], $credit1['id']);
        $this->assertEquals($creditTransactions['items'][1]['credits_used'], 10000);
        $this->assertEquals($creditTransactions['items'][1]['credits_id'], $credit2['id']);
    }

    /**
     *  This is to make sure that credits
     *  are used first which are expiring first
     */
    public function testCreditTransactionWithFeeCreditWithOldFlowForPrepaid()
    {
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 34000,
                       'expired_at' => time() + 2*24*60*60,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'fee',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['fee_credits' => 0]);

        $this->fixtures->merchant->addFeatures(['old_credits_flow']);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest();

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEmpty($creditTransactions['items']);
    }

    // Fee Model = Prepaid
    // Fee Bearer = Platform
    // Amount Credit > 0
    public function testTransactionOnCaptureWithAmountCreditForPrepaid()
    {
        $this->fixtures->create('credits', [
            'type'  => 'amount',
            'value' => 14000,
        ]);

        $this->fixtures->create('credits', [
            'type'  => 'amount',
            'value' => 10000,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 24000]);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 10000);
        $this->assertEquals($creditTransactions['items'][1]['credits_used'], 14000);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 0);
        $this->assertTrue($transaction['gratis']);
        $this->assertEquals($transaction['tax'], 0);
        $this->assertEquals($transaction['credit_type'], 'amount');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    /**
     *  This is to make sure that credits
     *  are used first which are expiring first
     */
    public function testCreditTransactionWithAmountCreditWithOldFlowForPrepaid()
    {
        //These never expire. Should be used at last
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 1000000,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'amount',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 30]);

        $this->fixtures->merchant->addFeatures(['old_credits_flow']);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest();

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 30);
        $this->assertEquals($creditTransactions['items'][0]['credits_id'], $credit2['id']);
    }

    /**
     *  This is to make sure that credits
     *  are used first which are expiring first
     */
    public function testCreditTransactionWithAmountCreditForPrepaid()
    {
        //These never expire. Should be used at last
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 1000000,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'amount',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest();

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 990000);
        $this->assertEquals($creditTransactions['items'][0]['credits_id'], $credit1['id']);
        $this->assertEquals($creditTransactions['items'][1]['credits_used'], 10000);
        $this->assertEquals($creditTransactions['items'][1]['credits_id'], $credit2['id']);
    }


    // Fee Model = Prepaid
    // Fee Bearer = Customer
    public function testTransactionOnCaptureWithFeeBearerCustomer()
    {
        $merchant = $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'fee'              => 23000
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest(null, 977000);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($transaction['credit_type'], 'default');
        $this->assertEquals($transaction['fee_bearer'], 'customer');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    // Fee Model = Prepaid
    // Fee Bearer = Customer
    // Amount Credit > 0
    public function testTransactionOnCaptureWithAmountCreditForFeeBearerCustomer()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 24000]);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'fee'              => 23000
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest(null, 977000);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 0);
        $this->assertTrue($transaction['gratis']);
        $this->assertEquals($transaction['tax'], 0);
        $this->assertEquals($transaction['credit_type'], 'amount');
        $this->assertEquals($transaction['fee_bearer'], 'customer');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    // Fee Model = Prepaid
    // Fee Bearer = Customer
    // Fee Credit > 0
    public function testTransactionOnCaptureWithFeeCreditForFeeBearerCustomer()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['fee_credits' => 24000]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'fee'              => 23000
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest(null, 977000);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], $transaction['fee_credits']);
        $this->assertEquals($transaction['credit_type'], 'fee');
        $this->assertEquals($transaction['fee_bearer'], 'customer');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    // Fee Model = Postpaid
    // Fee Bearer = Platform
    // Amount Credit > 0
    public function testTransactionOnCaptureWithAmountCreditForPostpaid()
    {
         //These never expire. Should be used at last
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 20000,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'amount',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 24000]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 0);
        $this->assertEquals($transaction['tax'], 0);
        $this->assertTrue($transaction['gratis']);
        $this->assertEquals($transaction['credit_type'], 'amount');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'postpaid');
    }

    // Fee Model = Postpaid
    // Fee Bearer = Platform
    // Fee Credit > 0
    public function testTransactionOnCaptureWithFeeCreditForPostpaid()
    {
        $credit1 = $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 20000,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'fee',
                       'value' => 10000,
                       'expired_at' => time() + 1*24*60*60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['fee_credits' => 24000]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 23600);
        $this->assertEquals($transaction['tax'], 3600);
        $this->assertEquals($transaction['fee_credits'], 23600);
        $this->assertEquals($transaction['credit_type'], 'fee');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'postpaid');
    }

    // Fee Model = Postpaid
    // Fee Bearer = Platform
    public function testTransactionOnCaptureForPostpaid()
    {
        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'        => '10ZeroPricingP',
            'feature'        => 'payment',
            'payment_method' => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 1000000);
        $this->assertEquals($transaction['fee'], 23600);
        $this->assertEquals($transaction['tax'], 3600);
        $this->assertEquals($transaction['credit_type'], 'default');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'postpaid');
    }

    public function startBulkTest(array $payments)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setBulkRequestData($testData['request'], $payments);
        $this->setBulkResponseData($testData['response'], $payments);

        return $this->runRequestResponseFlow($testData);
    }

    /**
     * Since the response data contains the count of payments in test
     * it would be better if we set the "count" and "success" dynamically.
     * This way, in the future, if we added more count to this test,
     * it would not require us to change the fixture.
     */
    protected function setBulkResponseData(& $response, $payments)
    {
        $response['content']['count']   = count($payments);
        $response['content']['success'] = count($payments);
    }

    protected function setBulkRequestData(& $request, $payments)
    {
        $request['content']['payment_ids'] = [];

        foreach ($payments as $payment)
        {
            $request['content']['payment_ids'][] = $payment['id'];
        }

        $url = '/payments/capture/bulk';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $id, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        $this->checkAndSetIdAndAmount($id, $amount);

        $request['content']['amount'] = $amount;

        $url = '/payments/'.$id.'/capture';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        if ($amount === null)
        {
            if (isset($this->payment['amount']))
                $amount = $this->payment['amount'];
        }
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
              ->with('payment', Mockery::type('RZP\Models\\Base\\PublicEntity'));
    }

    /**
     * Helper method which creates order, invoices and attempts to make a payment
     * which must fail.
     *
     * @param string  $paymentCapture
     * @param boolean $withInvoice
     *
     * @return array
     */
    protected function createFailedPayment($paymentCapture = '1', $withInvoice = true)
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $order = $this->fixtures->create(
            'order',
            [
                'id'              => '100000000order',
                'payment_capture' => $paymentCapture,
            ]);

        if ($withInvoice)
        {
            $dueBy = Carbon::now(Timezone::IST)->addDays(10)->timestamp;

            $this->fixtures->create(
                                'invoice',
                                [
                                    'due_by' => $dueBy,
                                    'amount' => 1000000,
                                ]);
        }

        $this->gateway = 'hdfc';

        $this->mockServerVerifyContentFunction();

        $this->gateway = null;

        $this->doAuthPaymentAndCatchException($order);

        $payment = $this->getLastEntity('payment', true);

        $this->assertInternalErrorCode($payment, 'GATEWAY_ERROR_UNKNOWN_ERROR');

        return $payment;
    }

    protected function mockServerVerifyContentFunction()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                throw new Exception\GatewayErrorException('GATEWAY_ERROR_UNKNOWN_ERROR');
            }

            if ($action === 'inquiry')
            {
                $content['RESPCODE'] = '0';
                $content['RESPMSG'] = 'Transaction succeeded';
                $content['STATUS'] = 'TXN_SUCCESS';
            }

            return $content;
        });
    }

    protected function doAuthPaymentAndCatchException($order)
    {
        return $this->makeRequestAndCatchException(function () use ($order)
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['amount'] = $order->getAmount();
            $payment['order_id'] = $order->getPublicId();

            $content = $this->doAuthPayment($payment);

            return $content;
        });
    }

    protected function assertInternalErrorCode($payment, $internalErrorCode)
    {
        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($internalErrorCode, $payment['internal_error_code']);
    }

    protected function mockProcessorRequest(Merchant\Entity $merchant, $times = 1)
    {
        $class = Payments\Processor\Processor::class;

        $processor = Mockery::mock($class, [$merchant])
                        ->makePartial();

        $processor->shouldReceive('saveFeeDetails')
            ->times($times)
            ->withAnyArgs()
            ->andThrow(new Exception\LogicException(
                    ErrorCode::BAD_REQUEST_FEE_BREAKUP_CREATION_FAILED));

    }
}
