<?php

namespace RZP\Tests\Functional\Gateway\Paysecure;

use App;
use Mail;
use Queue;
use Illuminate\Support\Facades\Redis;

use RZP\Gateway\Hitachi;
use RZP\Services\DowntimeMetric;
use RZP\Gateway\Paysecure\Entity;
use RZP\Gateway\Paysecure\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\Capture as CaptureJob;
use RZP\Exception\GatewayTimeoutException;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaysecureGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $paymentEntityGateway = 'hitachi';

    const HITACHI_MID = 'sample_hitachi_mid';
    const HITACHI_TID = 'sample_hitachi_tid';

    protected $terminal;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaysecureGatewayTestData.php';

        parent::setUp();

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');

        $this->terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ],
            'gateway_merchant_id' => self::HITACHI_MID,
            'gateway_terminal_id' => self::HITACHI_TID,
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

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'Ménage12345678901234567890']);

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
        // Assert that the terminal owner does not contain non-alpha numeric characters
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'validate_terminal_owner_name')
                {
                    $this->assertEquals('Mnage12345678901234567', $content);

                    $this->assertLessThanOrEqual(23, strlen($content));
                }
            }
        );

        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'validate_message_type')
                {
                    $this->assertEquals('SMS', $content);
                }
            }
        );

        $authResponse = $this->doAuthPayment($this->payment);

        $this->assertSuccess($authResponse, 'redirect');

        return $authResponse;
    }

    public function testStanIncrementAndTtl()
    {
        $redis = Redis::connection()->client();

        $this->testPaymentAuthViaRedirect();

        $counter = $redis->get(Gateway::GATEWAY_PAYSECURE_STAN);

        $this->assertEquals(1, $counter);

        $this->testPaymentAuthViaRedirect();

        $counter = $redis->get(Gateway::GATEWAY_PAYSECURE_STAN);
        $ttl = $redis->ttl(Gateway::GATEWAY_PAYSECURE_STAN);

        // Assert that the counter is increased and that the ttl is set for the same.
        // We can't check the value of ttl, since it depends on the current time and it changes every second
        $this->assertEquals(2, $counter);
        $this->assertGreaterThan(0, $ttl);

        $redis->set(Gateway::GATEWAY_PAYSECURE_STAN, 999999);

        $this->testPaymentAuthViaRedirect();
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'validate_stan')
                {
                    // Assert that the stan gets resetted to 0 once it reaches 999999
                    $this->assertEquals('000000', $content);
                }
            }
        );
    }

    public function testLocalCustomersPaymentAuthViaRedirect()
    {
        $this->fixtures->iin->edit('607384', ['message_type' => 'DMS']);
        // Create token and card
        $payment = $this->payment;

        $payment['customer_id'] = 'cust_100000customer';
        $payment['token'] = '10002cardtoken';

        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'validate_message_type')
                {
                    $this->assertEquals('DMS', $content);
                }
            }
        );

        $authResponse = $this->doAuthPayment($payment);

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

        $this->assertArraySelectiveEquals(
            [
                Entity::STATUS        => 'failure',
                Entity::ACTION        => 'authorize',
                Entity::ERROR_CODE    => '406',
                Entity::ERROR_MESSAGE => 'Not Authenticated',
            ],
            $gatewayPayment
        );
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

    public function testCallbackAutoCapture()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opaI',
                'merchant_id' => '10000000000000',
                'gateway' => 'hitachi',
                'enabled' => 1,
                'type' =>
                    [
                        'non_recurring' => '1',
                        'recurring_3ds' => '1',
                        'recurring_non_3ds' => '1'
                    ],
                'gateway_merchant_id' => 'sample_hitachi_mid',
                'gateway_terminal_id' => 'sample_hitachi_tid',
                'mode'                => 2,
            ]);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'        => 'captured',
                'amount'        => 50000,
                'method'        => 'card',
                'gateway'       => $this->paymentEntityGateway,
            ],
            $payment
        );

        $paysecure = $this->getDbLastEntityToArray('paysecure');

        $hitachi = $this->getDbLastEntityToArray('hitachi');

        $this->assertEquals(1, count($this->getDbEntities('hitachi')));

        $this->assertEquals($paysecure['payment_id'], $hitachi['payment_id']);

        $this->assertEquals($payment['terminal_id'], 'AqdfGh5460opaI');
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

                    $content['errorcode'] = 'CA';

                    $content['errormsg'] = 'Compliance error code for acquirer';
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

        $this->assertEquals([
            $this->gateway => [
                DowntimeMetric::Failure   => [
                    'SERVER_ERROR_INVALID_ARGUMENT' => 1,
                ]
            ],
        ], $this->app['gateway_downtime_metric']->getMetrics());
    }

    public function testAuthorizeFailureWithNoErrorMessage()
    {
        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'authorize')
                {
                    unset($content['apprcode']);

                    $content['status'] = 'failure';

                    $content['errorcode'] = '57';

                    unset($content['errormsg']);
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
                'action'     => 'capture',
                'pRespCode'  => '00',
                'payment_id' => substr($authResponse['razorpay_payment_id'],4),
            ],
            $hitachi
        );
    }

    public function testCaptureDispatchedOnTimeout()
    {
        Mail::fake();
        Queue::fake();

        $authResponse = $this->testPaymentAuthViaRedirect();

        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                // Hitachi's advice uses the same action response as that of callback
                if ($action === 'callback')
                {
                    throw new GatewayTimeoutException('Timed out');
                }
            },
            'hitachi'
        );
        $this->capturePayment($authResponse['razorpay_payment_id'], '50000');

        $payment = $this->getLastEntity('payment', true);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return ($payment['id'] === $data['payment']['public_id']);
        });

        Mail::assertQueued(CapturedMail::class);
    }

    public function testCaptureDispatchedOnFailure()
    {
        Mail::fake();
        Queue::fake();

        $authResponse = $this->testPaymentAuthViaRedirect();

        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                // Hitachi's advice uses the same action response as that of callback
                if ($action === 'callback')
                {
                    $decoded = json_decode($content, true);

                    $decoded['pRespCode'] = 'Z3';

                    $content = json_encode($decoded);
                }
            },
            'hitachi'
        );
        $this->capturePayment($authResponse['razorpay_payment_id'], '50000');

        $payment = $this->getLastEntity('payment', true);

        Queue::assertPushed(CaptureJob::class, function ($job) use ($payment)
        {
            $data = $job->getData();

            return ($payment['id'] === $data['payment']['public_id']);
        });

        Mail::assertQueued(CapturedMail::class);
    }

    public function testPaymentRefundViaHitachi()
    {
        $this->testPaymentSettledViaHitachi();

        $payment = $this->getDbLastEntityToArray('payment');

        //temporary: make gateway hitachi until paysecure has not been added to scrooge
        $this->gateway = 'hitachi';

        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'validateRefund') {
                    $sentMid = $content[Hitachi\RequestFields::MERCHANT_ID];
                    $sentTid = $content[Hitachi\RequestFields::TERMINAL_ID];

                    $this->assertEquals(self::HITACHI_MID, $sentMid);

                    $this->assertEquals(self::HITACHI_TID, $sentTid);
                }
            }
        );

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

    // For terminal mode "purchase", capture would not be called
    // And for this payments, we send the advice message for late auth payments
    // when the verify exception is caught.
    // This test case covers if this advice message is called. If the advise message is not called,
    // the hitachi entity would not exist.
    public function testLateAuthorizedViaPurchaseTerminal()
    {
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 2,

            ]
        );

        $this->mockServerContentFunction(
            function (&$content, $action = null)
            {
                if ($action === 'authorize')
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

        $data = $this->testData['testVerifyFailedPayment'];

        $payment = $this->getDbLastEntity('payment');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment->getPublicId());
        });

        $hitachi = $this->getDbLastEntityToArray('hitachi');

        $this->assertEquals($payment['id'], $hitachi['payment_id']);

        $this->assertNotNull($hitachi['pRRN']);

        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 3,
            ]
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
                'error_message'          => null,
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
