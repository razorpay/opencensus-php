<?php

namespace RZP\Tests\Functional\Gateway\Sharp;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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
        $this->otpCommonFlow('100000');
    }

    public function testOtpFlowIncorrectOtpPayment()
    {
        $this->otpCommonFlow('200000');
    }

    public function testOtpFlowOtpExpiredPayment()
    {
        $this->otpCommonFlow('300000');
    }

    public function testOtpFlowAttemptsExceededPayment()
    {
        $this->otpCommonFlow('400000');
    }

    protected function otpCommonFlow($otp)
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp($otp);

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];
        $data = $this->testData[$name];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
