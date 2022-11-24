<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Mpesa;

use Mockery\Exception;
use SoapFault;
use ErrorException;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Mpesa\Action;
use RZP\Gateway\Wallet\Mpesa\SoapAction;
use Illuminate\Testing\TestResponse;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MpesaGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'mpesa';

    const OTP = '1234';

    protected $payment;

    protected $sharedTerminal;

    protected function setUp(): void
    {
        $this->markTestSkipped();
        
        $this->testDataFilePath = __DIR__ . '/MpesaGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mpesa_terminal');

        $this->gateway = 'wallet_mpesa';

        $this->fixtures->merchant->enableWallet('10000000000000', self::WALLET);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->setOtp(self::OTP);
    }

    public function testAuthPayment()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals($testData, $payment);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testAuthPaymentWalletEntity');

        $this->assertNotEmpty($wallet['gateway_payment_id']);

        $this->assertEmpty($wallet['gateway_payment_id_2']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['txnAmt'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    /**
     * The purpose of this test to ensure that
     * float payments don't cause an issue when
     * array_flip is called when generating request xml
     */
    public function testAuthFloatPayment()
    {
        //
        // Changing amount to 500.50/-
        //
        $this->payment['amount'] = '50050';

        $testData = $this->testData[__FUNCTION__];

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals($testData, $payment);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testAuthFloatPaymentWalletEntity');

        $this->assertNotEmpty($wallet['gateway_payment_id']);

        $this->assertEmpty($wallet['gateway_payment_id_2']);
    }

    public function testVerifyCallbackFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->mockActionFailure(SoapAction::QUERY_API);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testAuthPaymentFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->mockActionFailure(Action::AUTHORIZE);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testAuthPaymentVerify()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $verify = $this->verifyPayment($payment['id']);

        $this->assertArraySelectiveEquals($data, $verify);

        $this->assertNotEmpty($verify['gateway']['verifyResponseContent']['transRefNum']);
        $this->assertNotEmpty($verify['gateway']['verifyResponseContent']['MSISDN']);
    }

    public function testAuthPaymentSuccessVerifyFailed()
    {
        $data = $this->testData['testVerifyMismatch'];

        $expectedWallet = $this->testData['verifyFailedWalletEntity'];

        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockActionFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            }
        );

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertArraySelectiveEquals($expectedWallet, $wallet);

        $this->assertNotEmpty($wallet['gateway_payment_id']);
    }

    public function testAuthPaymentFailedVerifySuccess()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testAuthPaymentFailure();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $wallet = $this->getLastEntity('wallet', true);

        $data = $this->testData['verifySuccessWalletEntity'];

        $this->assertArraySelectiveEquals($data, $wallet);

        $this->assertNotEmpty($wallet['gateway_payment_id']);
    }

    public function testRefundPayment()
    {
        $this->refundsTest(50000, __FUNCTION__);
    }

    public function testPartialRefund()
    {
        $this->refundsTest(10000, __FUNCTION__);
    }

    public function testRefundFailed()
    {
        $this->testAuthPayment();

        $this->mockActionFailure(SoapAction::REFUND_API);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('failed', $refund['status']);
    }

    public function testSoapTimeoutError()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSoapFault(true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    public function testSoapError()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSoapFault();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    public function testSoapSslError()
    {
        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSoapSslError();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    public function testMpesaUpperCaseError()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $payment['wallet'] = 'MPESA';

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    /**
     * We assert that a GatewayErrorException is thrown when verify returns
     * a timeout or any other exception during the authorize failed step
     */
    public function testAuthorizeFailedNullVerifyResponse()
    {
        $this->testAuthPaymentFailure();

        $this->mockSoapFault();

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->authorizeFailedPayment($payment['public_id']);
            });
    }

    protected function mockSoapSslError()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                throw new ErrorException('SoapClient::__doRequest(): SSL: Connection reset by peer');
            });
    }

    protected function mockSoapFault($timeout = false)
    {
        $this->mockServerContentFunction(function(& $content, $action = null) use ($timeout)
        {
            if (($timeout === true) and
                ($action === SoapAction::QUERY_API))
            {
                throw new SoapFault('HTTP', 'Error Fetching http headers');
            }
            else if ($action === SoapAction::QUERY_API)
            {
                throw new SoapFault('HTTP', 'Random SoapFault Exception');
            }
        });
    }

    protected function refundsTest(int $amount, string $key)
    {
        $data = $this->testData[$key];

        $this->testAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('refund', true);

        $this->assertArraySelectiveEquals($data, $refund);
    }

    protected function mockActionFailure($method = null)
    {
        $this->mockServerContentFunction(function(& $content, $action = null) use ($method)
        {
            //
            // For cases when the mock response to be failed is after
            // a couple of steps in the flow that need to pass
            // For eg. testCallbackOtpSubmitFailure needs the response
            // to be a failure only in the OTP_SUBMIT stage.
            //
            if (($method) and
                ($method !== $action))
            {
                return;
            }

            switch ($action)
            {
                case Action::AUTHORIZE:
                    $content['statuscode'] = '106';
                    break;

                case SoapAction::OTP_SUBMIT_API:
                    $content['statusCode'] = '104';
                    $content['mcomPgTransID'] = "";
                    break;

                default:
                    $content['statusCode'] = '104';
                    break;
            }
        });
    }

    protected function runPaymentCallbackFlowWalletMpesa(TestResponse $response, string &$callback = null)
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
