<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Bajaj;

use RZP\Gateway\Wallet;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BajajGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BajajGatewayTestData.php';

        parent::setUp();

        $this->wallet = Payment\Processor\Wallet::BAJAJPAY;
        $this->fixtures->merchant->enableAdditionalWallets([$this->wallet]);
        $this->terminal = $this->fixtures->create('terminal:shared_bajaj_terminal');
        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getDbLastPayment()->toArray();
        $this->assertTestResponse($payment);
    }

    public function testIncorrectPaymentOtp()
    {
        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::INCORRECT);

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['otp_attempts']);

        $this->assertEquals('failed', $payment['two_factor_auth']);
    }

    public function testOtpRetrySuccessPayment()
    {
        $data = $this->testData['testIncorrectPaymentOtp'];

        $this->setOtp(Otp::INCORRECT);

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastPayment();

        $this->assertEquals('BAD_REQUEST_PAYMENT_OTP_INCORRECT', $payment['internal_error_code']);

        $data['request']['url'] = $this->getOtpResendUrl($payment->getPublicId());

        $this->makeRequestAndGetContent($data['request']);

        $data['request'] = [
            'url' => $this->getOtpSubmitUrl($payment),
            'content' => [
                'type' => 'otp',
                'otp'  => '1234',
            ],
        ];

        $this->makeRequestAndGetContent($data['request']);

        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment = $payment->reload()->toArray();

        $this->assertTestResponse($payment, 'testPayment');
    }

    protected function runPaymentCallbackFlowWalletBajaj($response, &$callback = null)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        return $this->makeOtpCallback($url);
    }
}
