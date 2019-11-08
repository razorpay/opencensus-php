<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingScbGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingScbGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->bank = 'SCBL';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_scb_terminal');
    }

    public function testPayment()
    {
        $this->doNetbankingScbAuthAndCapturePayment();

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity);

        $mozartEntity = $this->getDbLastEntityToArray('mozart', 'test');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentMozartEntity'], $mozartEntity);
    }

    public function testTamperedAmount()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['amount'] = '1230';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['paymentId'] = 'ABCD1234567890'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }



    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingScbAuthAndCapturePayment();
        });
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['success'] = false;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);
    }

    protected function doNetbankingScbAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->doAuthAndCapturePayment($payment);
    }
}
