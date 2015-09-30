<?php

namespace Tests\Functional\Gateway\Paytm;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class PaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaytmGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paytm_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enablePaytm('10000000000000');

        $this->gateway = 'paytm';
    }

    public function testPayment()
    {
        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('paytm', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentPaytmEntity'], $payment);
    }

    public function testPaytmWallet()
    {
        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'paytm';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('paytm', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaytmWalletEntity'], $payment);
    }

    public function testFailedPayment()
    {
        $this->markTestIncomplete();
    }

    public function testPayment3dsecureFailed()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }

    public function testVerifyPayment()
    {
        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'paytm';
        $payment = $this->doAuthAndCapturePayment($payment);

        $id = $payment['id'];

        $data = $this->verifyPayment($id);

        $this->assertEquals($data['payment']['verified'], true);
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('paytm', true);

        $this->assertTestResponse($refund);
    }

    public function testPaytmWhenNotEnabled()
    {
        $this->fixtures->merchant->disablePaytm('10000000000000');

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'paytm';

        $testData['request']['content'] = $payment;

        $content = $this->startTest($testData);
    }

    public function testRefundByAdminOnAuthorizedPayment()
    {
        $payment = $this->defaultAuthPayment();

        $this->ba->proxyAuth();

        $input['force'] = '1';
        $content = $this->refundAuthorizedPayment($payment['id'], $input);

        $this->assertEquals('refund', $content['entity']);
    }
}
