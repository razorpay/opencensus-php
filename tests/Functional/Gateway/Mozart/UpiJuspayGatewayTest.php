<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;

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

        $this->terminal = $this->fixtures->create('terminal:upi_juspay_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment()
    {
        $this->payment['description'] = 'success';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');

        $payment = $this->getDbLastEntity('payment');

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());


        $response = $this->makeRequestAndGetContent($request);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        // The payment should now be authorized
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($paymentId, $payment['amount']);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('captured', $payment['status']);

        return $payment;
    }

    public function testFailedCallbackResponse()
    {
        $this->payment['description'] = 'failedCallback';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');

        $payment = $this->getDbLastEntity('payment');

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $this->makeRequestAndCatchException(function () use ($request) {
            $this->makeRequestAndGetContent($request);
        },
            GatewayErrorException::class,
            'Payment failed because UPI request expired'.PHP_EOL.
            'Gateway Error Code: gateway_error_code'.PHP_EOL.
            'Gateway Error Desc: gateway_error_desc');

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);

        return $payment;
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
