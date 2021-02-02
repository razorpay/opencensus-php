<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;

class UpiJuspayGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    public function setUp()
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

        $request = $this->mockServer('mozart')->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment->refresh();

        $this->assertTrue($payment->isAuthorized());
        $this->assertNotNull($payment->getRefundAt());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
          UpiEntity::TYPE    => UpiBase\Type::COLLECT,
          UpiEntity::ACTION  => 'authorize',
          UpiEntity::GATEWAY => 'upi_juspay',
        ], $upi->toArray());

        return $payment;
    }

    public function testFailedCallbackResponse()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'failedCallback';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCreated());

        $request = $this->mockServer('mozart')->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response, ['success' => false]);

        $payment->refresh();

        $this->assertArraySubset([
            Entity::STATUS              => 'failed',
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED',
            Entity::ERROR_DESCRIPTION   => 'Payment failed because UPI request expired',
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

        $request = $this->mockServer('mozart')->getCallbackRequest($payment->toArray());

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

        $request = $this->mockServer('mozart')->getCallbackRequest($payment->toArray());

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

        $request = $this->mockServer('mozart')->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $payment->refresh();

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testCreateFailedPaymentAndVerifySuccess()
    {
        $this->createTestTerminal();

        $this->payment['description'] = 'paymentCreateFailed';

        $this->makeRequestAndCatchException(function () {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $mozart = $this->getDbLastMozart();
        $this->assertNotNull($mozart);
        $this->assertSame('authorize', $mozart->getAction());

        $payment = $this->getDbLastPayment();
        $this->assertSame('failed', $payment->getStatus());

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $this->verifyAllPayments();

        $payment->reload();

        $this->assertSame('authorized', $payment->getStatus());
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
}
