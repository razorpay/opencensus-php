<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiJuspayGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'mozart';

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
        ], $payment->toArray());

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment->refresh();

        $this->assertTrue($payment->isAuthorized());

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

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $this->makeRequestAndCatchException(
            function () use ($request) {
                $this->makeRequestAndGetContent($request);
            },
            GatewayErrorException::class,
            'Payment failed because UPI request expired' . PHP_EOL .
            'Gateway Error Code: gateway_error_code' . PHP_EOL .
            'Gateway Error Desc: gateway_error_desc');

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
            Refund\Entity::STATUS       => 'failed',
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

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $payment->refresh();

        $this->assertEquals('authorized', $payment['status']);
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
