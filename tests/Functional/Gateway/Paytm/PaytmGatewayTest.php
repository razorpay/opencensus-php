<?php

namespace RZP\Tests\Functional\Gateway\Paytm;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class PaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        // Paytm codebase is no longer used anywhere
        // PayTM has stopped working with aggregators since March 2016.

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
        $payment['bank'] = 'MAHB';
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
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'MAHB';

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify_refund')
            {
                $content['body']['resultInfo'] = [
                    'resultStatus' => 'TXN_FAILURE',
                    'resultCode'   => '631',
                    'resultMsg'    => 'Record not found'
                ];
            }

            return $content;
        });

        $refundResponse = $this->refundPayment($payment['id']);

        $refundEntity = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refundEntity['status']);

        return $refundResponse;
    }

    public function testVerifyRefund()
    {
        $refund = $this->testRefundPayment();

        $this->mockServerContentFunction(function (& $content, $action = null) {
            return $content;
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
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

    public function testPaymentInvalidHash()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['CHECKSUMHASH'] = 'failed';

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthWalletPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testPaymentAmountMismatch()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['TXNAMOUNT'] = '10.00';

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthWalletPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['ORDERID'] = 'random';

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthWalletPayment();
        });

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
