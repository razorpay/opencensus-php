<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Cybersource;
use RZP\Tests\Functional\TestCase;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->sharedHdfcTerminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $amount = $payment['amount'];

        $payment = $this->doAuthPayment($payment);

        $cybersourceAuth = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceAuthEntity'], $cybersourceAuth);

        $payment = $this->capturePayment($payment['razorpay_payment_id'], $amount);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $cybersourceCapture = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $cybersourceCapture);
    }

    public function testFailedAuthPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4280951000002433';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayError()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000400000000004';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayTimeoutError()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '41476700000006';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayWithSavedCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['token'] = '1000gcardtoken';
        $payment['app_token'] = 'capp_1000000custapp';

        $data = $this->testData[__FUNCTION__];

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

    }

    public function testNotEnrolledPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testNotEnrolledCSEntity'], $payment);
    }

    public function testAuthenticationFailurePayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111460212312338';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            return $this->doAuthPayment($payment);
        });
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentRefund'], $refund);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refundAmount = (int) ($payment['amount'] / 5);

        $this->mockServerContentFunction(function($content) use ($refundAmount)
        {
            $actualRefundAmount = (int) ($content['purchaseTotals']['grandTotalAmount'] * 100);

            $assertion = ($actualRefundAmount === $refundAmount);

            $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
        });

        $this->refundPayment($payment['id'], $refundAmount);

        $refund = $this->getLastEntity('cybersource', true);

        $this->assertTestResponse($refund);
    }

    public function testAuthPaymentRefund()
    {
        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment();

        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($response['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($response['razorpay_payment_id'], $refund['payment_id']);
        $this->assertArraySelectiveEquals(
            $this->testData['testAuthPaymentRefund'], $refund);
    }

    public function testVerifyPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $verifyResponse = $this->verifyPayment($payment['id']);

        $this->assertSame($verifyResponse['payment']['verified'], 1);
        $this->assertSame($verifyResponse['gateway']['gatewayPayment']['status'], 'authorized');
    }

    public function testVerifyCapturedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testAuthorizeFailedPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4280951000002433';

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testAuthorizeFailedPayment'], $cybersource);
    }
}
