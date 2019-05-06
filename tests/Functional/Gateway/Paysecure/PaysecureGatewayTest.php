<?php

namespace RZP\Tests\Functional\Gateway\Paysecure;

use RZP\Gateway\Paysecure\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaysecureGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $paymentEntityGateway = 'hitachi';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaysecureGatewayTestData.php';

        parent::setUp();

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ],
            'gateway_merchant_id' => 'sample_hitachi_mid',
            'gateway_terminal_id' => 'sample_hitachi_tid',
        ]);

        $merchantDetailArray = [
            'contact_name'                => 'rzp',
            'contact_email'               => 'test@rzp.com',
            'merchant_id'                 => '10000000000000',
            'business_registered_address' => 'Koramangala',
            'business_registered_state'   => 'KARNATAKA',
            'business_registered_pin'     => 560047,
            'business_dba'                => 'test',
            'business_name'               => 'rzp_test',
            'business_registered_city'    => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->fixtures->iin->create([
            'iin'          => '607384',
            'country'      => 'IN',
            'issuer'       => 'PUNB',
            'network'      => 'RuPay',
            'message_type' => 'SMS',
            'flows'        => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ],
        ]);

        $this->gateway = 'paysecure';

        $this->setMockGatewayTrue();

        $this->mockCardVault();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPaymentAuthViaRedirect()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'redirect');

        return $authResponse;
    }

    public function testPaymentAuthViaRedirectForBlacklistedMcc()
    {
        $this->addBlacklistConfig(['6012' => '7994']);

        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'redirect');
    }

    public function testS2SPaymentAuthViaRedirect()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $authResponse = $this->doS2SPrivateAuthPayment($this->payment);

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
                'gateway' => $this->paymentEntityGateway,
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
                    $content['status']      = 'failure';
                    $content['errorcode']   = '406';
                    $content['errormsg']    = 'Not Authenticated';
                    $content['RedirectURL'] = '';
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
                'gateway' => $this->paymentEntityGateway,
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertNotEmpty($gatewayPayment);
    }

    public function testCallbackFailure()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'auth_response')
                {
                    $content['AccuResponseCode'] = 'ACCU100';
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
                'status'        => 'failed',
                'amount'        => 50000,
                'method'        => 'card',
                'gateway'       => $this->paymentEntityGateway,
                'verify_bucket' => null,
                'verify_at'     => null,
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
                'gateway' => $this->paymentEntityGateway,
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
                'gateway' => $this->paymentEntityGateway,
            ],
            $payment
        );

        $gatewayPayment = $this->getDbLastEntityToArray('paysecure');

        $this->assertNotEmpty($gatewayPayment);
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

    public function testPaymentSettledViaHitachi()
    {
        $authResponse = $this->testPaymentAuthViaRedirect();

        $this->capturePayment($authResponse['razorpay_payment_id'], '50000');

        $hitachi = $this->getDbLastEntityToArray('hitachi');

        $this->assertArraySelectiveEquals(
            [
                'action'     => 'authorize',
                'pRespCode'  => '00',
                'payment_id' => substr($authResponse['razorpay_payment_id'],4),
            ],
            $hitachi
        );
    }

    public function testPaymentRefundViaHitachi()
    {
        $this->testPaymentSettledViaHitachi();

        $payment = $this->getDbLastEntityToArray('payment');

        //temporary: make gateway hitachi until paysecure has not been added to scrooge
        $this->gateway = 'hitachi';

        $this->refundPayment('pay_' . $payment['id'], 1000);

        $refund = $this->getDbLastEntityToArray('refund');

        // Hitachi refunds goes via Scrooge
        // So, we can only check the refund entity, since the other things are handled at Scrooge
        $this->assertArraySelectiveEquals(
            [
                'amount'     => 1000,
                'payment_id' => $payment['id'],
                'gateway'    => 'hitachi',
                'is_scrooge' => true,
                'status'     => 'processed',
            ],
            $refund
        );

        //temporary: make gateway paysecure for other testcases
        $this->gateway = 'paysecure';
    }

    public function testPaymentRefundWithMissingRrnViaHitachi()
    {
        $this->testPaymentSettledViaHitachi();

        $payment = $this->getDbLastEntityToArray('payment');

        $this->clearMockFunction();

        //temporary: make gateway hitachi until paysecure has not been added to scrooge
        $this->gateway = 'hitachi';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['pStatus'] = 'Error';
            }

            if ($action === 'refund')
            {
                unset($content['pRRN']);
            }

        });

        $this->refundPayment('pay_' . $payment['id'], 1000);

        $refund = $this->getDbLastEntityToArray('refund');

        // Hitachi refunds goes via Scrooge
        // So, we can only check the refund entity, since the other things are handled at Scrooge
        $this->assertArraySelectiveEquals(
            [
                'amount'     => 1000,
                'payment_id' => $payment['id'],
                'gateway'    => 'hitachi',
                'is_scrooge' => true,
                'status'     => 'processed',
            ],
            $refund
        );

        //temporary: make gateway paysecure for other testcases
        $this->gateway = 'paysecure';
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
                'gateway' => $this->paymentEntityGateway,
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
                    'gateway'  => $this->paymentEntityGateway,
                    'verified' => 1,
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
                'gateway' => $this->paymentEntityGateway,
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

        $cacheDriver = $this->app['config']->get('cache.secure_default');

        $key = sprintf(Gateway::CACHE_KEY, $payment['id']);

        $cacheValue = $this->app['cache']->store($cacheDriver)->get($key);

        $this->assertArraySelectiveEquals(
            [
                'vault_token' => base64_encode($this->payment['card']['number']),
            ],
            $cacheValue
        );

        // Ensure cvv does not get stored in cache
        $this->assertArrayNotHasKey('cvv', $cacheValue);

        $this->assertArraySelectiveEquals(
            [
                'id'      => substr($authResponse['razorpay_payment_id'], 4),
                'status'  => 'authorized',
                'amount'  => 50000,
                'method'  => 'card',
                'gateway' => $this->paymentEntityGateway,
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

        $this->assertNotNull($gatewayPayment['rrn']);
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '6073849700004947',
            'name'              => 'Test user',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }

    protected function addBlacklistConfig(array $mapping)
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:paysecure_blacklisted_mccs' => $mapping
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }
}
