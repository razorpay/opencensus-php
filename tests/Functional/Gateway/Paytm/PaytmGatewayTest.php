<?php

namespace RZP\Tests\Functional\Gateway\Paytm;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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

    public function testPayment3dsecureFailed()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }

    public function testRefundPayment()
    {
        // HACK, remove once paytm refunds are done
        $this->markTestSkipped('Skipped as a hack for now');

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

        $payment = $this->getDefaultWalletPaymentArray('paytm');

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

    public function testVerifyPayment()
    {
        $this->setMockGatewayTrue();

        $payment = $this->getDefaultWalletPaymentArray('paytm');
        $payment = $this->doAuthAndCapturePayment($payment);

        $id = $payment['id'];

        $data = $this->verifyPayment($id);

        $this->assertEquals($data['payment']['verified'], 1);
    }

    public function testFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->timeoutAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);
        // Payment should be in created state because it had timed out
        $this->assertEquals('created', $payment['status']);
        $this->fixtures->payment->failPayment($payment['id']);

        $this->succeedPaymentVerify();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
    }

    protected function failAuthorizePayment()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['RESPCODE'] = '18';
            $content['RESPMSG'] = 'Transaction failed';
            $content['STATUS'] = 'TXN_FAILURE';

            return $content;
        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $content = $this->doAuthWalletPayment();
            });
    }

    protected function timeoutAuthorizePayment()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\GatewayTimeoutException('Timed out');
        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $content = $this->doAuthWalletPayment();
            });
    }

    protected function succeedPaymentVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['RESPCODE'] = '0';
            $content['RESPMSG'] = 'Transaction succeeded';
            $content['STATUS'] = 'TXN_SUCCESS';

            return $content;
        });
    }
}
