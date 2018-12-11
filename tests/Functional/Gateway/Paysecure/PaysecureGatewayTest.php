<?php

namespace RZP\Tests\Functional\Gateway\Paysecure;

use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaysecureGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaysecureGatewayTestData.php';

        parent::setUp();

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');

        $this->fixtures->create('terminal:shared_paysecure_terminal');

        $this->gateway = 'paysecure';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPaymentAuthViaRedirect()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'redirect');
    }

    /**
     * Error response from CheckBin request.
     * Verify the same and make sure verify responds with action finish
     */
    public function testUnqualifiedPin()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'checkbin2')
                {
                    $content['status']                = 'failure';
                    $content['qualified_internetpin'] = 'FALSE';
                    $content['errorcode']             = '410';
                    $content['errormsg']              = 'Invalid BIN';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertEmpty($gatewayPayment);
    }

    public function testInititiate2Failure()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'initiate2')
                {
                    $content['status']                = 'failure';
                    $content['errorcode']             = '406';
                    $content['errormsg']              = 'Not Authenticated';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertEmpty($gatewayPayment);
    }

    public function testCallbackFailure()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'auth_response')
                {
                    $content['AccuResponseCode'] = 'ACCU600';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );
    }

    public function testAuthorizeFailure()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'authorize')
                {
                    unset($content['apprcode']);

                    $content['status'] = 'failure';

                    $content['errorcode'] = '57';

                    $content['errormsg'] = 'DECLINED (cardholder not allowed)';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );
    }

    public function testInititiateFailure()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'checkbin2')
                {
                    $content['Implements_Redirect'] = 'FALSE';
                }
                if ($action === 'initiate')
                {
                    $content['status']                = 'failure';
                    $content['errorcode']             = '406';
                    $content['errormsg']              = 'Not Authenticated';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertEmpty($gatewayPayment);
    }

    public function testPaymentAuthViaPinPad()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'checkbin2')
                {
                    $content['Implements_Redirect'] = 'FALSE';
                }
            }
        );

        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'iframe');
    }

    public function testSoapFault()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'checkbin2')
                {
                    throw new \SoapFault('Server', 'connection timed out');
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'failed',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertEmpty($gatewayPayment);
    }

    public function testPaymentVerifyForRedirectFlow()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'redirect');

        $verify = $this->verifyPayment($authResponse['razorpay_payment_id']);

        $this->assertArraySelectiveEquals(
            [
                'payment' => [
                    'gateway' => $this->gateway,
                    'verified' => 1
                ]
            ],
            $verify
        );
    }

    public function testVerifyFailedPayment()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'auth_response')
                {
                    throw new GatewayTimeoutException('Timed out');
                }
            }
        );

        $data = $this->testData['testAuthorizeFailed'];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $payment = $this->getDbLastEntity('payment');

            $this->verifyPayment($payment->getPublicId());
        });

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'method' => 'card',
                'gateway' => $this->gateway,
                'amount' => 50000,
                // Verify mismatch
                'verified' => 0,
            ],
            $payment
        );

        $paysecure = $this->getDbLastEntityToArray('paysecure');

        $this->assertNotNull($paysecure['apprcode']);
    }

    protected function assertSuccess($authResponse, $flow)
    {
        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'id'      => substr($authResponse['razorpay_payment_id'], 4),
                'status'  => 'authorized',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->gateway
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertArraySelectiveEquals(
            [
                'payment_id'             => $payment['id'],
                'received'               => true,
                'status'                 => 'success',
                'gateway_transaction_id' => '100000000000000000000000025236',
                'error_code'             => '00',
                'error_message'          => '',
                'flow'                   => $flow,
                'apprcode'               => '183217',
            ],
            $gatewayPayment
        );
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '6073849700004947',
            'name'              => 'Praveen',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }
}
