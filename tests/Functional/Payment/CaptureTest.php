<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Queue;
use Mockery;
use Carbon\Carbon;
use RZP\Exception;
use Dashboard\Payment;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Processor\Processor;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\Capture as CaptureJob;
use RZP\Mail\Merchant\BalancePositiveAlert;
use RZP\Mail\Merchant\NegativeBalanceAlert;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
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
    use MocksRazorx;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $testData = null;

    protected $payment = null;

    protected function setUp(): void
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

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testCaptureWithOrderOutbox()
    {
        $orderId = $this->fixtures->generateUniqueId();

        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('fetchOrder')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $orderId, string $merchantId, array $input)
            {
                $this->fixtures->stripSign($orderId);

                $order = [
                    "id"            => $orderId,
                    "amount"        => 1000,
                    "amount_paid"   => 0,
                    "amount_due"    => 1000,
                    "currency"      => "INR",
                    "receipt"       => "test_auto_capture_receipt",
                    "offer_id"      => null,
                    "status"        => "created",
                    "attempts"      => 0,
                    "notes"         => [],
                    "created_at"    => 1683543840,
                    "merchant_id"   => "10000000000000"
                ];

                return (new Order\Entity())->forceFill($order);
            });

        $createdAt = Carbon::now()->subMinutes(15)->getTimestamp(); // 15 minutes; should be captured
        $updatedAt = $createdAt;
        $refundAt = Carbon::createFromTimestamp($createdAt)->addHour()->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:authorized', [
            'created_at'        => $createdAt,
            'updated_at'        => $updatedAt,
            'authorized_at'     => $updatedAt,
            'order_id'          => $orderId,
            'amount'            => '1000',
            'refund_at'         => $refundAt,
            'gateway_captured'  => true,
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::ORDER_OUTBOX_ONBOARDING, 'on');

        $this->ba->privateAuth();
        $this->startTest();

        $payment = $this->getEntityById('payment', $payment->getId(), true);
        $orderOutbox = $this->getLastEntity('order_outbox', true);

        $this->assertEquals(true, $payment['gateway_captured']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($orderId, $orderOutbox['order_id']);
    }

    public function testCaptureFailedWithQueue()
    {
        Mail::fake();
        Queue::fake();

        $this->fixtures->merchant->addFeatures(['capture_queue']);

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new Exception\RuntimeException;
            }
        });

        $this->ba->privateAuth();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(null, $payment['gateway_captured']);
        $this->assertEquals('captured', $payment['status']);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return $payment['id'] === $data['payment']['public_id'];
        });

        Queue::assertPushedOn('capture_test', CaptureJob::class);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testCapturedMailForCurlecMerchant()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');
        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());

        $this->fixtures->merchant->edit('10000000000000', [
            'org_id'    => $org->getId()
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        Mail::assertQueued(CapturedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.payment.merchant');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@curlec.com', $mail->replyTo[0]['address']);

            $this->assertEquals('no-reply@curlec.com', $mail->from[0]['address']);

            return true;
        });
    }

    public function testAsyncCaptureWithQueue()
    {
        Mail::fake();
        Queue::fake();

        $this->fixtures->merchant->addFeatures(['async_capture']);

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'hdfc';

        $this->ba->privateAuth();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(null, $payment['gateway_captured']);
        $this->assertEquals('captured', $payment['status']);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return $payment['id'] === $data['payment']['public_id'];
        });

        Queue::assertPushedOn('capture_test', CaptureJob::class);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testAsyncCaptureWithQueueSmsAuthorized()
    {
        Mail::fake();
        Queue::fake();

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'RuPay',
            'message_type' => 'SMS',
            'flows'   => [
                '3ds'          => '1',
                'otp'          => '1',
                'ivr'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        Mail::fake();
        Queue::fake();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $response = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(null, $payment['gateway_captured']);
        $this->assertEquals('authorized', $payment['status']);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return $payment['id'] === $data['payment']['public_id'];
        });

        Queue::assertPushedOn('capture_test', CaptureJob::class);

    }

    public function testAsyncCaptureWithQueueSmsAuthorizedGatewayCaptureCheck()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'RuPay',
            'message_type' => 'SMS',
            'flows'   => [
                '3ds'          => '1',
                'otp'          => '1',
                'ivr'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5567630000002004';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTrue($payment['gateway_captured']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('paysecure', $payment['gateway']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('paysecure', $payment['gateway']);
    }

    public function testAsyncCaptureWithQueueDmsAuthorizedGatewayCaptureCheck()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'RuPay',
            'message_type' => 'DMS',
            'flows'   => [
                '3ds'          => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5567630000002004';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTrue($payment['gateway_captured']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('paysecure', $payment['gateway']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('paysecure', $payment['gateway']);
    }

    public function testGatewayCaptureForAllPaymentsRazorpayOrgMastercard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::PAYMENT_GATEWAY_CAPTURE_ASYNC_MC, 'on');

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTrue($payment['gateway_captured']);

        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $payment['gateway']);
    }

    public function testGatewayCaptureForAllPaymentsRazorpayOrg()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::PAYMENT_GATEWAY_CAPTURE_ASYNC_OTHER_NETWORKS, 'on');

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTrue($payment['gateway_captured']);

        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $payment['gateway']);
    }

    public function testNotGatewayCaptureForAllPaymentsNonRazorpayOrg()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4264511038488895';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['gateway_captured']);

        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $payment['gateway']);
    }

    public function testDelayCaptureRupay()
    {
        Mail::fake();
        Queue::fake();

        $this->enableRupayCaptureDelayConfig();

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'RuPay',
            'message_type' => 'DMS',
            'flows'   => [
                '3ds'          => '1',
                'otp'          => '1',
                'ivr'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5567630000002004';

        $response = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['gateway_captured']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paysecure', $payment['gateway']);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return $payment['id'] === $data['payment']['public_id'];
        });

        Queue::assertPushedOn('capture_test', CaptureJob::class);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testCaptureFailedWithoutQueue()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new Exception\RuntimeException;
            }
        });

        $this->ba->privateAuth();

        $this->startTest($payment['id'], $payment['amount']);
    }

    public function testCaptureTimeoutWithoutQueue()
    {
        Mail::fake();
        Queue::fake();

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new Exception\GatewayTimeoutException('curl 35:');
            }
        });

        $this->ba->privateAuth();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(null, $payment['gateway_captured']);
        $this->assertEquals('captured', $payment['status']);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return $payment['id'] === $data['payment']['public_id'];
        });

        Queue::assertPushedOn('capture_test', CaptureJob::class);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testCaptureTimeoutWithoutQueueNonHdfc()
    {
        Mail::fake();
        Queue::fake();

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'axis_migs';

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new Exception\GatewayTimeoutException('curl 35:');
            }
        });

        $this->ba->privateAuth();

        $this->startTest($payment['id'], $payment['amount']);
    }

    public function testBulkCapture()
    {
        Mail::fake();

        $count = 3;

        $payments = [];

        for ($i=0; $i < $count; $i++) {
            $payments[] = $this->defaultAuthPayment();
        }

        $this->ba->adminAuth();

        $this->startBulkTest($payments);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(CapturedMail::class);
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

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paid', $order['status']);

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

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paid', $order['status']);

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

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('paid', $order['status']);

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
        $this->assertEquals('paid', $order['status']);
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

        $this->refundPayment($payment['id']);

        $this->ba->privateAuth();

        $this->payment = $payment;

        $this->startTest();
    }

    public function testAutoCapture()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);
        $this->app['config']->set('gateway.mock_atom', true);
        $cardId = $this->fixtures->create('card')['id'];

        $this->fixtures->merchant->editAutoRefundDelay('1 hours');
        $this->fixtures->merchant->editLateAuthAutoCapture(true);

        $order = $this->fixtures->create('order:payment_capture_order');

        $orderId = $order->getId();

        $createdAt = Carbon::now()->subMinutes(15)->getTimestamp(); // 15 minutes; should be captured
        $updatedAt = $createdAt;
        $refundAt = Carbon::createFromTimestamp($createdAt)->addHour()->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:authorized', [
                'created_at'    => $createdAt,
                'updated_at'    => $updatedAt,
                'authorized_at' => $updatedAt,
                'order_id'      => $orderId,
                'card_id'       => $cardId,
                'refund_at'     => $refundAt,
            ]);

        $this->fixtures->merchant->editLateAuthAutoCapture(true, '1cXSLlUU8V9sXl');

        $order = $this->fixtures->create('order:payment_capture_order', [
            'merchant_id'   => '1cXSLlUU8V9sXl',
        ]);
        $orderId = $order->getId();

        $createdAt = Carbon::now()->subMinutes(15)->getTimestamp(); // 15 minutes;
        $updatedAt = $createdAt;
        // 5 days - default value for auto_refund_delay
        $refundAt = Carbon::createFromTimestamp($createdAt)->addDays(5)->getTimestamp();

        // merchant with default refund delay; should be captured
        $payment = $this->fixtures->create(
            'payment:authorized', [
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
            'authorized_at' => $updatedAt,
            'order_id'      => $orderId,
            'card_id'       => $cardId,
            'merchant_id'   => '1cXSLlUU8V9sXl',
            'refund_at'     => $refundAt,
        ]);

        $order = $this->fixtures->create('order:payment_capture_order');

        $orderId = $order->getId();

        $createdAt = Carbon::now()->subHours(2)->getTimestamp();    // 2 hours; should not be captured
        $updatedAt = $createdAt;
        $refundAt = Carbon::createFromTimestamp($createdAt)->addHour()->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:authorized', [
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
            'authorized_at' => $updatedAt,
            'order_id'      => $orderId,
            'card_id'       => $cardId,
            'refund_at'     => $refundAt,
        ]);

        $this->fixtures->merchant->editAutoRefundDelay('1 hours', '10NodalAccount');

        $order = $this->fixtures->create('order:payment_capture_order', [
           'merchant_id'   => '10NodalAccount',
        ]);

        $orderId = $order->getId();

        $createdAt = Carbon::now()->subMinutes(15)->getTimestamp(); // 15 minutes;
        $updatedAt = $createdAt;
        $refundAt = Carbon::createFromTimestamp($createdAt)->addHour()->getTimestamp();

        // merchant does not have late_auth_auto_capture; should not be captured
        $payment = $this->fixtures->create(
            'payment:authorized', [
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
            'authorized_at' => $updatedAt,
            'order_id'      => $orderId,
            'card_id'       => $cardId,
            'merchant_id'   => '10NodalAccount',
            'refund_at'     => $refundAt,
        ]);

        $order = $this->fixtures->create('order:payment_capture_order');

        $orderId = $order->getId();

        $createdAt = Carbon::now()->subMinutes(15)->getTimestamp(); // 15 minutes
        $updatedAt = $createdAt;
        $refundAt = Carbon::createFromTimestamp($createdAt)->addHour()->getTimestamp();

        // payment in created state; should not be captured
        $payment = $this->fixtures->create(
            'payment:created', [
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
            'order_id'      => $orderId,
            'card_id'       => $cardId,
            'merchant_id'   => '10000000000000',
            'refund_at'     => $refundAt,
        ]);

        //order without auto capture
        $order = $this->fixtures->create('order');

        $orderId = $order->getId();

        //order without auto capture, should not be captured
        $payment = $this->fixtures->create(
            'payment:authorized', [
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
            'authorized_at' => $updatedAt,
            'order_id'      => $orderId,
            'card_id'       => $cardId,
            'refund_at'     => $refundAt,
        ]);

        $content = $this->doAutoCapture();

        $this->assertSame(2, $content['count']);
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
                       'expired_at' => time() + 2 * 24 * 60 * 60,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'fee',
                       'value' => 10000,
                       'expired_at' => time() + 1 * 24 * 60 * 60,
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
                       'expired_at' => time() + 2 * 24 * 60 * 60,
                   ]);

        $credit2 = $this->fixtures->create('credits', [
                       'type'  => 'fee',
                       'value' => 10000,
                       'expired_at' => time() + 1 * 24 * 60 * 60,
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
    // Amount Credit >= Payment amount
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
            'gateway_captured' => true,
            'amount'           => 24000
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

        $this->assertEquals($transaction['credit'], 24000);
        $this->assertEquals($transaction['fee'], 0);
        $this->assertTrue($transaction['gratis']);
        $this->assertEquals($transaction['tax'], 0);
        $this->assertEquals($transaction['credit_type'], 'amount');
        $this->assertEquals($transaction['fee_bearer'], 'platform');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    // Fee Model = Prepaid
    // Fee Bearer = Platform
    // Amount Credit < Payment amount
    public function testTransactionOnCaptureWithAmountCreditLessThanAmountForPrepaid()
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
            'gateway_captured' => true,
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals(0, sizeof($creditTransactions['items']));

        //As fee will get charged
        $this->assertNotEquals($transaction['credit'], 1000000);
        $this->assertNotEquals($transaction['fee'], 0);
        $this->assertFalse($transaction['gratis']);
        $this->assertNotEquals($transaction['tax'], 0);
        $this->assertEquals($transaction['credit_type'], 'default');
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
                       'expired_at' => time() + 1 * 24 * 60 * 60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 10000]);

        $this->fixtures->merchant->addFeatures(['old_credits_flow']);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'amount'           => 5000,
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest();

        $creditTransactions = $this->getEntities('credit_transaction', [], true);

        $this->assertEquals($creditTransactions['items'][0]['credits_used'], 5000);
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
                       'expired_at' => time() + 1 * 24 * 60 * 60,
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
    // Payment Fee Bearer = Customer
    // Merchant fee bearer = customer
    public function testTransactionOnCaptureWithPaymentFeeBearerCustomerMerchantFeeBearerCustomer()
    {
        $merchant = $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'fee'              => 23000,
            'fee_bearer'       => Merchant\FeeBearer::CUSTOMER,
        ]);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => Merchant\FeeBearer::CUSTOMER]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();

        $this->startTest(null, 977000);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($transaction['credit_type'], 'default');
        $this->assertEquals($transaction['fee_bearer'], 'customer');
        $this->assertEquals($transaction['fee_model'], 'prepaid');
    }

    // Fee Model = Prepaid
    // Payment Fee Bearer = Customer
    // Merchant fee bearer = dynamic
    public function testTransactionOnCaptureWithPaymentFeeBearerCustomerMerchantFeeBearerDynamic()
    {
        $merchant = $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'fee'              => 23000,
            'fee_bearer'       => Merchant\FeeBearer::CUSTOMER,
        ]);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => Merchant\FeeBearer::CUSTOMER]);

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
    // Amount Credit > payment amount
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
                       'expired_at' => time() + 1 * 24 * 60 * 60,
                   ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['credits' => 24000]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $pricing = $this->fixtures->base->createEntity('pricing', [
            'plan_id'           => '10ZeroPricingP',
            'feature'           => 'payment',
            'payment_method'    => 'card'
        ]);

        $payment = $this->fixtures->create('payment:authorized', [
            'gateway_captured' => true,
            'amount'           => 20000,
        ]);

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('authorized', $hdfc['status']);
        $this->assertEquals('APPROVED', $hdfc['result']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['credit'], 20000);
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
                       'expired_at' => time() + 1 * 24 * 60 * 60,
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

    public function testTransactionOnCaptureForIssuerBasedPricing()
    {
        $this->fixtures->create('pricing',[
            'id'                  => '1nvp2XPMmaRLxd',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 200,
            'international'       => 0,
            'org_id'              => '100000razorpay',
        ]);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $this->doAuthAndCapturePayment($payment);
        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(200, $transaction['fee']);

        $this->fixtures->create('pricing',[
            'id'                  => '1nvp2XPMmaRLxc',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => null,
            'payment_issuer'      => 'HDFC',
            'percent_rate'        => 0,
            'fixed_rate'          => 500,
            'international'       => 0,
            'org_id'              => '100000razorpay',
        ]);

        $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(500, $transaction['fee']);
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
        $this->makeRequestAndCatchException(function() use ($order)
        {
            $payment             = $this->getDefaultPaymentArray();
            $payment['amount']   = $order->getAmount();
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

    public function testAutoRefundCaptureOnLateAuthorizedPaymentWithAutoRefund()
    {
        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay'      => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $order = $this->fixtures->create(
            'order',
            [
                'id'              => '100000000order',
                'payment_capture' => true,
            ]);

        $payment2 = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 1000000,
            'contact'       => '9918899029',
            'method'        => 'wallet',
            'wallet'        => 'payumoney',
            'gateway'       => 'wallet_payumoney',
            'card_id'       => null,
            'order_id'      => '100000000order'
        ]);

        $payment1 = $this->getDefaultPaymentArray();

        $payment1['amount'] = 1000000;

        $payment1['order_id'] = 'order_100000000order';

        $payment1 = $this->doAuthPayment($payment1);

        $payment1 = $this->getDbLastEntity('payment');

        $payment2 = $this->authorizeFailedPayment($payment2->getPublicId());

        $order   = $this->getLastEntity('order', true);

        $now = Carbon::now()->getTimestamp();

        $this->assertEquals('captured', $payment1->getStatus());

        $this->assertEquals('authorized', $payment2['status']);

        $this->assertLessThanOrEqual( $now, $payment2['refund_at']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testRefundAtOnManualCaptureOnLateAuthorizedPayment()
    {
        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay'      => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $order = $this->fixtures->create(
            'order',
            [
                'id'              => '100000000order'
            ]);

        $payment2 = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 1000000,
            'contact'       => '9918899029',
            'method'        => 'wallet',
            'wallet'        => 'payumoney',
            'gateway'       => 'wallet_payumoney',
            'card_id'       => null,
            'order_id'      => '100000000order'
        ]);

        $payment1 = $this->getDefaultPaymentArray();

        $payment1['amount'] = 1000000;

        $payment1['order_id'] = 'order_100000000order';

        $payment1 = $this->doAuthPayment($payment1);

        $payment1 = $this->getDbLastEntity('payment');

        $this->authorizeFailedPayment($payment2->getPublicId());

        //Capture first payment
        $payment1 = $this->capturePayment($payment1->getPublicId(), '1000000');

        $now = Carbon::now()->getTimestamp();
        //Capture second payment
       $this->makeFailedCaptureRequest($payment2->getPublicId(), '1000000');

        $payment2 = $this->getEntityById('payment', $payment2->getId(), true);

        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('captured', $payment1['status']);

        $this->assertEquals('authorized', $payment2['status']);

        $this->assertLessThanOrEqual( $payment2['refund_at'], $now);

        $this->assertEquals('paid', $order['status']);
    }

    public function makeFailedCaptureRequest($id, $amount, $currency = 'INR') {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount));

        if ($currency !== 'INR')
        {
            $request['content']['currency'] = $currency;
        }

        $this->ba->privateAuth();
        $this->makeRequestAndCatchException(function () use ($request) {
            $this->makeRequestAndGetContent($request);
        },
            Exception\BadRequestValidationFailureException::class,
            'Corresponding order already has a captured payment.');
    }

    // Tests if Optimizer payment gets auto captured with capture settings enabled
    public function testOptimizerExternalPgPaymentAutoCapture()
    {

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay' => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id','raas']);

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'config' => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 1,
                    "refund_speed": "normal"
                }
            }']);

        $order = $this->fixtures->create(
            'order',
            [
                'id' => '100000000order',
                'payment_capture' => true,
            ]);

        $terminal = $this->fixtures->create('terminal:card_payu_terminal');

        $this->enableCpsConfig();

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::ENABLE_CAPTURE_SETTINGS_FOR_OPTIMIZER, 'on');

        $payment1 = $this->getDefaultPaymentArray();

        $payment1['force_terminal_id'] = 'term_'.$terminal->getId();

        $payment1['amount'] = 1000000;

        $payment1['order_id'] = 'order_100000000order';

        $payment1 = $this->doAuthPayment($payment1);

        $payment1 = $this->getDbLastEntity('payment');

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('captured', $payment1->getStatus());

        $this->assertEquals('paid', $order['status']);
    }

    // Tests if Optimizer payment gets auto captured within timeout with capture settings enabled
    public function testOptimizerExternalPgPaymentAutoCaptureWithinTimeout()
    {
        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay' => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id','raas']);

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'config' => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 10,
                    "refund_speed": "normal"
                }
            }']);

        $order = $this->fixtures->create(
            'order',
            [
                'id' => '100000000order',
                'payment_capture' => true,
            ]);

        $terminal = $this->fixtures->create('terminal:card_payu_terminal');

        $this->enableCpsConfig();

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::ENABLE_CAPTURE_SETTINGS_FOR_OPTIMIZER, 'on');

        $cardId = $this->fixtures->create('card')['id'];

        $payment1 = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 1000000,
            'contact'       => '9918899029',
            'method'        => 'card',
            'gateway'       => 'payu',
            'order_id'      => '100000000order',
            'terminal_id'   => $terminal->getId(),
            'card_id'       => $cardId,
            'cps_route'     => 2
        ]);

         // 3 min difference --> we have set auto capture timeout to 10 above.
        $past = Carbon::now(Timezone::IST)->subMinutes(3)->timestamp;
        $this->fixtures->payment->edit($payment1['id'], ['created_at' => $past]);

        $this->authorizeFailedPayment($payment1->getPublicId());

        $payment1 = $this->getEntityById('payment', $payment1->getId(), true);

        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('captured', $payment1['status']);

        $this->assertEquals('paid', $order['status']);
    }

    // Tests if Optimizer payment is not captured and refund_at is set if late authorized beyond timeout
    public function testOptimizerExternalPgPaymentAutoRefund()
    {
        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_refund_delay' => '2 days',
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id','raas']);

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'config' => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 10,
                    "refund_speed": "normal"
                }
            }']);

        $order = $this->fixtures->create(
            'order',
            [
                'id' => '100000000order',
                'payment_capture' => true,
            ]);

        $terminal = $this->fixtures->create('terminal:card_payu_terminal');

        $this->enableCpsConfig();

        $this->mockRazorxTreatmentV2(Merchant\RazorxTreatment::ENABLE_CAPTURE_SETTINGS_FOR_OPTIMIZER, 'on');

        $cardId = $this->fixtures->create('card')['id'];

        $payment1 = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 1000000,
            'contact'       => '9918899029',
            'method'        => 'card',
            'gateway'       => 'payu',
            'order_id'      => '100000000order',
            'terminal_id'   => $terminal->getId(),
            'card_id'       => $cardId,
            'cps_route'     => 2
        ]);

        // 15 min difference --> we have set auto capture timeout to 10 above.
        $past = Carbon::now(Timezone::IST)->subMinutes(15)->timestamp;
        $this->fixtures->payment->edit($payment1['id'], ['created_at' => $past]);

        $expectedRefundAt = Carbon::createFromTimestamp($past, Timezone::IST)
                ->addMinutes(10)->getTimestamp();

        $this->authorizeFailedPayment($payment1->getPublicId());

        $payment1 = $this->getEntityById('payment', $payment1->getId(), true);

        $order   = $this->getLastEntity('order', true);

        $this->assertEquals('authorized', $payment1['status']);

        $this->assertEquals($expectedRefundAt, $payment1['refund_at']);

        $this->assertEquals('created', $order['status']);
    }
}
