<?php

namespace Tests\Functional\Gateway\Sharp;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class SharpGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/SharpGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testPaymentWithGet()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4111111111111111';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }


    public function testPaymentFailed()
    {
        $this->failPaymentOnBankPage = true;

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();
        $testData['request']['content'] = $payment;
        $this->startTest($testData);
    }

    public function testOtpFlowInsufficientBalancePayment()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp('100000');

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testOtpFlowIncorrectOtpPayment()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp('200000');

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testOtpFlowOtpExpiredPayment()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp('300000');

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testOtpFlowAttemptsExceededPayment()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp('400000');

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }
}
