<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Gateway\Upi\Juspay;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\P2p\Upi\Axis\Mock;
use RZP\Exception\RuntimeException;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Tests\P2p\Service\Base\Fixtures;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;

class UpiJuspayGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_juspay';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'success';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_juspay',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
        ], $payment->toArray());

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment->refresh();

        $this->assertTrue($payment->isAuthorized());
        $this->assertNotNull($payment->getRefundAt());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
          UpiEntity::TYPE                => UpiBase\Type::COLLECT,
          UpiEntity::ACTION              => 'authorize',
          UpiEntity::GATEWAY             => 'upi_juspay',
          UpiEntity::RECEIVED            => 1,
          UpiEntity::NPCI_TXN_ID         => 'APP34749005b22e45bfa1e9a38e668fc43c',
          UpiEntity::NPCI_REFERENCE_ID   => '034520388334',
          UpiEntity::MERCHANT_REFERENCE  => $payment->getId(),
        ], $upi->toArray());

        return $payment;
    }

    public function testP2pCallbackOnJuspay()
    {
        $deviceSetMap = [
            Fixtures\Fixtures::DEVICE_1 => [
                'merchant'      => Fixtures\Fixtures::TEST_MERCHANT,
                'customer'      => Fixtures\Fixtures::RZP_LOCAL_CUSTOMER_1,
                'device'        => Fixtures\Fixtures::CUSTOMER_1_DEVICE_1,
                'handle'        => Fixtures\Fixtures::RAZOR_AXIS,
                'bank_account'  => Fixtures\Fixtures::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
                'vpa'           => Fixtures\Fixtures::CUSTOMER_1_VPA_1_AXIS,
            ]
        ];

        $fix = new Fixtures\Fixtures($deviceSetMap);

        $vpa = $fix->vpa(Fixtures\Fixtures::DEVICE_1);

        $sdk = new Mock\Sdk();
        $sdk->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            'amount' => '100.00',
            'payerVpa' => 'customer@razoraxis',
            'payeeVpa' => $vpa->getAddress(),
            'merchantCustomerId' => 'DEMO-CUST-1234',
        ]);

        $callbackBody = $sdk->callback();

        $callbackBodyArray = json_decode($callbackBody['content'], $options = 'JSON_OBJECT_AS_ARRAY');

        $request = $this->mockServer('upi_juspay')->getCallback([
            'amount' => 1,
            'id' => '',
            'vpa' => ''
        ],
            $callbackBodyArray);

        $request['server'] = array_merge($request['server'], $callbackBody['server']);

        $request['raw'] = $callbackBody['content'];

        $this->makeRequestAndGetContent($request);
    }

    public function testFailedCallbackResponse()
    {
        $this->createTestTerminal();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCreated());

        $request = $this->mockServer('upi_juspay')
                        ->getCallback($payment->toArray(),
                            [
                                Juspay\Fields::GATEWAY_RESPONSE_CODE => 'U69'
                            ]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response, ['success' => false]);

        $payment->refresh();

        $this->assertArraySubset([
            Entity::STATUS              => 'failed',
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED',
            Entity::ERROR_DESCRIPTION   => 'Payment was unsuccessful as you could not pay with the UPI app within time.',
        ], $payment->toArray());

        return $payment;
    }

    public function testRefundPayment()
    {
        $payment = $this->testPayment();

        $response = $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $response = $this->refundPayment($payment->getPublicId());

        $this->assertArraySubset([
            Refund\Entity::PAYMENT_ID   => $payment->getPublicId(),
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            Entity::STATUS              => 'refunded',
        ], $payment->toArray());

        $refund = $this->getDbLastRefund();

        $this->assertArraySubset([
            Refund\Entity::PAYMENT_ID   => $payment->getId(),
            Refund\Entity::AMOUNT       => $payment->getAmount(),
            Refund\Entity::STATUS       => 'processed',
            Refund\Entity::GATEWAY      => $payment->getGateway(),
            Refund\Entity::GATEWAY_REFUNDED   => true
        ], $refund->toArray());
    }

    public function testFailedRefundPayment()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'failedRefund';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['success' => true], $response);

        $payment->refresh();

        $this->assertTrue($payment->isAuthorized());

        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment->refresh();

        $this->assertTrue($payment->isCaptured());

        $response = $this->refundPayment($payment->getPublicId(), $payment->getAmount());

        $payment->refresh();

        $this->assertArraySubset([
            Entity::STATUS => 'refunded'
        ], $payment->toArray());

        $refund = $this->getDbLastRefund();

        $this->assertArraySubset([
            Refund\Entity::PAYMENT_ID   => $payment->getId(),
            Refund\Entity::AMOUNT       => $payment->getAmount(),
            // Through scrooge during failure, status is set to created
            Refund\Entity::STATUS       => RefundStatus::CREATED,
            Refund\Entity::GATEWAY      => $payment->getGateway(),
            Refund\Entity::GATEWAY_REFUNDED   => false
        ], $refund->toArray());
    }

    public function testIntentPayment()
    {
        $this->enableIntentFlow();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray(), [
            Juspay\Fields::PAYER_VPA  => 'customer@xyz',
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $payment->refresh();

        $this->assertEquals('authorized', $payment['status']);

        $this->assertNotNull($payment->getVpa());

        $this->assertSame('customer@xyz', $payment->getVpa());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::TYPE    => UpiBase\Type::PAY,
            UpiEntity::ACTION  => 'authorize',
            UpiEntity::GATEWAY => 'upi_juspay',
        ], $upi->toArray());
    }

    public function testIntentPaymentWhenRefIdAbsent()
    {
        $this->enableIntentFlow();

        $this->payment['description'] = 'intentWithRefIdAbsent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $payment->refresh();

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testPaymentForV2Contracts()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'collect_request_failed_v2';

        $this->makeRequestAndCatchException(function ()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastPayment();

        $this->assertSame('failed', $payment->getStatus());

        $this->payment['description'] = 'collect_request_success_v2';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $payment->refresh();

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testIntentPaymentForV2Contracts()
    {
        $this->enableIntentFlow();

        $this->payment['description'] = 'intent_request_success_v2';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray(), [
            Juspay\Fields::PAYER_VPA => 'varun@abfspay',
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertSame(true, $response['success']);

        $payment->reload();

        $this->assertArraySubset([
             Entity::STATUS      => 'authorized',
             Entity::REFERENCE16 => '034520388334',
             Entity::VPA         => 'varun@abfspay',
        ], $payment->toArray());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            'vpa'               => 'varun@abfspay',
            'npci_reference_id' => '034520388334',
            'bank'              => 'UTBI',
            'provider'          => 'abfspay',
        ], $upi->toArray());
    }

    /**
     * Tests the new amount assertion in callback flow.
     */
    public function testCallbackAmountMismatch()
    {
        $this->enableIntentFlow();

        $this->payment['description'] = 'callback_amount_mismatch_v2';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $request = $this->mockServer('upi_juspay')->getCallback($payment->toArray(), [
            Juspay\Fields::AMOUNT => '100.00',
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $payment->reload();

        $this->assertSame('failed', $payment->getStatus());

        $this->assertSame('SERVER_ERROR_AMOUNT_TAMPERED', $payment->getInternalErrorCode());
    }

    public function testLateAuthorizedPayment()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'late_authorized_v2';

        $this->makeRequestAndCatchException(function () {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastPayment();
        $this->assertSame('failed', $payment->getStatus());

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $this->verifyAllPayments();

        $payment->reload();

        $this->assertSame('authorized', $payment->getStatus());
        $this->assertTrue($payment->isLateAuthorized());
    }

    public function testVerifyPaymentAmountMismatch()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'verify_amount_mismatch_v2';

        $this->makeRequestAndCatchException(function () {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastPayment();
        $this->assertSame('failed', $payment->getStatus());

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->verifyPayment($payment->getPublicId());
        }, RuntimeException::class, 'Payment amount verification failed.');
    }

    public function testDirectPayment()
    {
        $this->createTestExpectedQrTerminal();

        $request = $this->mockServer('upi_juspay')->getDirectCallback($this->terminal, [
            Juspay\Fields::AMOUNT => '10.00'
        ]);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            Entity::AMOUNT        => 1000,
            Entity::MERCHANT_ID   => $this->terminal['merchant_id'],
            Entity::METHOD        => 'upi',
            Entity::GATEWAY       => 'upi_juspay',
            Entity::STATUS        => 'authorized',
            Entity::VPA           => 'customer@abfspay',
            Entity::REFERENCE16   => '034520388334',
        ], $payment->toArray());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::PAYMENT_ID           => $payment->getId(),
            UpiEntity::VPA                  => $payment->getVpa(),
            UpiEntity::NPCI_TXN_ID          => 'APP34749005b22e45bfa1e9a38e668fc43c',
            UpiEntity::NPCI_REFERENCE_ID    => $payment->getReference16(),
            UpiEntity::RECEIVED             => true,
            UpiEntity::AMOUNT               => $payment->getAmount(),
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::STATUS_CODE          => '00',
        ], $upi->toArray());
    }

    public function testDirectFailedPayment()
    {
        $this->createTestExpectedQrTerminal();

        $request = $this->mockServer('upi_juspay')->getDirectCallback($this->terminal, [
            Juspay\Fields::AMOUNT                   => '10.00',
            Juspay\Fields::GATEWAY_RESPONSE_CODE    => 'U30',
            Juspay\Fields::GATEWAY_RESPONSE_MESSAGE => 'Transaction failed',
            Juspay\Fields::MERCHANT_REQUEST_ID      => 'SomeGeneratedId'
        ]);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            Entity::AMOUNT        => 1000,
            Entity::MERCHANT_ID   => $this->terminal['merchant_id'],
            Entity::METHOD        => 'upi',
            Entity::GATEWAY       => 'upi_juspay',
            Entity::STATUS        => 'failed',
        ], $payment->toArray());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::PAYMENT_ID           => $payment->getId(),
            UpiEntity::VPA                  => 'customer@abfspay',
            UpiEntity::NPCI_TXN_ID          => 'APP34749005b22e45bfa1e9a38e668fc43c',
            UpiEntity::NPCI_REFERENCE_ID    => '034520388334',
            UpiEntity::RECEIVED             => true,
            UpiEntity::AMOUNT               => $payment->getAmount(),
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::STATUS_CODE          => 'U30',
            UpiEntity::MERCHANT_REFERENCE   => 'SomeGeneratedId',
        ], $upi->toArray());
    }

    protected function enableIntentFlow($description = 'intentPayment')
    {
        $this->terminal = $this->fixtures->create('terminal:upi_juspay_intent_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment['description'] = $description;

        $this->payment['_']['flow'] = 'intent';

        unset($this->payment['vpa']);
    }

    protected function createTestTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:upi_juspay_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }

    protected function createTestExpectedQrTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:upi_juspay_intent_terminal', [
            'expected' => 1
        ]);

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }
}
