<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Pnb;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingPnbGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setup()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingPnbGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_pnb';

        $this->bank = 'PUNB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testAuthFailedVerifyFailed()
    {
        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailedVerifyResponse();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    /**
     * When the payment is incorrectly marked as authorized
     * and verify points out that it is not
     */
    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailedVerifyResponse();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');
    }

    /**
     * Authorization fails, but verify shows success
     * Results in a payment verification error
     */
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSetVerifyTransactionId();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['BankStatus'] = 'F';
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['BankStatus'] = 'F';
            }
        });
    }

    protected function mockSetVerifyTransactionId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $gatewayPayment = $this->getLastEntity('netbanking', true);

            $content['txns'][0]['txnid'] = $gatewayPayment['bank_payment_id'];
        });
    }
}
