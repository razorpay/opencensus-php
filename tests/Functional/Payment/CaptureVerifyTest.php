<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentVerifyTrait;
use RZP\Tests\Functional\TestCase;


class CaptureVerifyTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use PaymentVerifyTrait;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/VerifyTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->cronAuth();

        $this->gateway = 'ebs';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');
    }

    public function testNotVerifiablePaymentForCaptureVerify()
    {
        $createdAt = Carbon::now()->getTimestamp() - 173000; // more than 2 days old timestamp

        $verifyAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $this->fixtures->create(
            'terminal', [
            'id'                   => 'AqdfGh5460opVt',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'hitachi',
            'gateway_merchant_id'  => '250000002',
            'gateway_merchant_id2' => 'abc@icici',
            'enabled'              => 1,
        ]);

        $card = $this->fixtures->create('card' , ['network'=>'RuPay']);

        // Not applicable as hitachi[rupay] payment and more than 2 days old
        $this->fixtures->create('payment', [
            'method'        => 'card',
            'gateway'       => 'hitachi',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $verifyAt,
            'captured_at'   => $createdAt,
            'card_id'       => $card->getId(),
            'amount'        => 100,
            'status'        => 'captured',
        ]);

        // Not applicable since reconciled
        $payment = $this->fixtures->create('payment', [
            'method'        => 'card',
            'gateway'       => 'hitachi',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $verifyAt,
            'verify_at'     => $verifyAt,
            'captured_at'   => $createdAt,
            'card_id'       => $card->getId(),
            'amount'        => 1000,
            'status'        => 'captured',
        ]);

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' => $payment->getId(), 'merchant_id' => '10000000000000',
            'reconciled_at' => Carbon::now()->getTimestamp()]);

        $this->fixtures->edit('payment',$payment->getId(), ['transaction_id'=> $transaction->getId()]);

        $this->startTest();
    }

    public function testCaptureVerifyHoldPayment()
    {
        $this->markTestSkipped("Feature not enabled atm.");

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['payment_onhold']);

        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->assertFalse($payment->getOnHold());
        $this->assertFalse($transaction->getOnHold());

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    'error_code_tag' => 'GW00201',
                    'error_service_tag' => 'null',
                    'result' => '!ERROR!-GW00201-Transaction not found.',
                ];
            }

            return $content;
        }, 'hdfc');

        $content = $this->makeRequestAndGetContent($request);

        $payment->reload();

        $transaction->reload();

        $this->assertTrue($payment->getOnHold());
        $this->assertTrue($transaction->getOnHold());

        // unset hold Payment
        $this->ba->adminAuth();

        $request = [
            'url' => '/payments/on_hold/bulk_update',
            'method' => 'POST',
            'content' => [
                'payment_ids' => [$payment->getPublicId()],
                'on_hold'     => 0,
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment->reload();

        $transaction->reload();

        $this->assertFalse($payment->getOnHold());
        $this->assertFalse($transaction->getOnHold());

    }

    public function testCaptureVerifyExcessOrderPayment()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['disable_amount_check','excess_order_amount']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $payments = $this->createMultipleFailedPaymentWithOrder($data);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('captured', $payment['status']);

        $payment = $this->getEntityById('payment', $payments[1], true);

        $this->assertEquals('captured', $payment['status']);

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('paid', $order['status']);
    }

    public function testLateAuthMerchantDefaultConfigVerifyPaymentWithPaymentCapture()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['disable_amount_check','excess_order_amount']);

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
                                           'config'     => '{
                "capture": "manual",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 13,
                    "refund_speed": "normal"
                }
            }']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $payments = $this->createMultipleFailedPaymentWithOrder($data);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('captured', $payment['status']);

        $payment = $this->getEntityById('payment', $payments[1], true);

        $this->assertEquals('captured', $payment['status']);

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('paid', $order['status']);
    }

    public function testLateAuthMerchantOrderConfigVerifyPaymentBeforeTimeoutForZeroCapture()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['disable_amount_check']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
            "capture"=> 'manual',
            "capture_options"=> [
                "manual_expiry_period"=> 20,
                "automatic_expiry_period"=> 13,
                "refund_speed"=> "normal"
            ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('authorized', $payment['status']);

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('attempted', $order['status']);
    }

    public function testLateAuthMerchantOrderConfigVerifyPaymentAfterTimeoutForZeroCapture()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['disable_amount_check']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
            "capture"=> 'manual',
            "capture_options"=> [
                "manual_expiry_period"=> 14,
                "automatic_expiry_period"=> 13,
                "refund_speed"=> "normal"
            ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $verifyAt = Carbon::now(Timezone::IST)->addMinutes(11)->getTimestamp();

        $this->fixtures->edit('payment', $payment['id'], ['verify_at' => $verifyAt]);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($payment['refund_at'], Carbon::createFromTimestamp($payment['created_at'])->addMinutes(14)->getTimestamp());

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('attempted', $order['status']);
    }

    protected function setMockGatewayTrue()
    {
        $var = 'gateway.mock_'.$this->gateway;

        $this->config[$var] = true;
    }
}
