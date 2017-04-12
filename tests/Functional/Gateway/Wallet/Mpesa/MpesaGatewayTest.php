<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Mpesa;

use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Mpesa\SoapAction;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MpesaGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'mpesa';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/MpesaGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mpesa_terminal');

        $this->gateway = 'wallet_mpesa';

        $this->fixtures->merchant->enableWallet('10000000000000', self::WALLET);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);
    }

    public function testOtpPayment()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals($testData, $payment);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testOtpPaymentWalletEntity');

        $this->assertNotEmpty($wallet['gateway_payment_id']);

        $this->assertNotEmpty($wallet['gateway_payment_id_2']);

        $this->assertNotEmpty($wallet['contact']);
    }

    public function testOtpCustomerValidationFailure()
    {
        $data = $this->testData['testAuthFailure'];

        $this->mockCustomerValidationFailure();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            }
        );
    }

    public function testOtpGenerationFailure()
    {
        $data = $this->testData['testAuthFailure'];

        $this->mockOtpGenerationFailure();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            }
        );
    }

    public function testCallbackOtpSubmitFailure()
    {
        $data = $this->testData['testAuthFailure'];

        $this->mockCallbackOtpSubmitFailure();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            }
        );
    }

    public function testOtpPaymentVerify()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testOtpPayment();

        $payment = $this->getLastEntity('payment', true);

        $verify = $this->verifyPayment($payment['id']);

        $this->assertArraySelectiveEquals($data, $verify);

        $this->assertNotEmpty($verify['gateway']['verifyResponseContent']['transRefNum']);
        $this->assertNotEmpty($verify['gateway']['verifyResponseContent']['MSISDN']);
    }

    public function testOtpPaymentSuccessVerifyFailed()
    {
        $data = $this->testData['testVerifyFailed'];

        $this->testOtpPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            }
        );
    }

    protected function mockCustomerValidationFailure()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === SoapAction::CUSTOMER_API)
            {
                $content['statusCode'] = '104';
                $content['description'] = 'Mobile number not found';
            }
        });
    }

    protected function mockOtpGenerationFailure()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === SoapAction::OTP_GENERATE_API)
            {
                $content['statusCode'] = '104';
                $content['description'] = 'Mobile number not found';
            }
        });
    }

    protected function mockCallbackOtpSubmitFailure()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === SoapAction::OTP_SUBMIT_API)
            {
                $content['statusCode'] = '104';
                $content['description'] = 'Mobile number not found';
            }
        });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === SoapAction::QUERY_API)
            {
                $content['statusCode'] = '104';
                $content['reason'] = 'Mobile number not found';
            }
        });
    }

    protected function runPaymentCallbackFlowWalletMpesa($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }

            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackRedirect($url);
        }

        return null;
    }
}
