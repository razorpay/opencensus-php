<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HeadlessOtpTest extends TestCase
{
    use PaymentTrait;

    public function testHeadlessOtpAuthenticationPayment()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2S()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 's2s']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);
        $content = $this->getJsonContentFromResponse($response);

        self::assertArrayHasKey('next', $content);
        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('created', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertEquals($content['razorpay_payment_id'], $response['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testOtpPreferredAuthPaymentWoFeatureFallback()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['otp'];

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWithoutTerminal()
    {
        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:shared_axis_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['otp'];

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('axis_migs', $payment['gateway']);
        self::assertEquals('1000AxisMigsTl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWith3dsFallback()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:shared_axis_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['otp'];

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authenticate')
            {
                throw new GatewayTimeoutException('Timed out', null, true);
            }
        }, 'mpi_blade');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('axis_migs', $payment['gateway']);
        self::assertEquals('1000AxisMigsTl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWithCardNotSupported()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        config(['app.data_store.mock' => false]);
        // Mocking mutex since we are mocking redis and partial mock
        // is difficult to mock (read as doesn't work) in laravel
        config(['services.mutex.mock' => true]);

        Redis::shouldReceive('zrevrange')
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'hdfc'       => '70',
                    'hitachi'    => '50',
                ];
            });

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['otp'];

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWith3dsAtPriority()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->iin->create([
            'iin' => '556763',
            'country' => 'IN',
            'issuer' => 'ICIC',
            'network' => 'MasterCard',
            'flows' => [
                '3ds' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds', 'otp'];

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        // It will be headless since we are identifying on the basis of the
        // terminal gateway not the terminal as there is no property of the terminal to
        // be used here
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWithString()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpAuthenticationWithNoTerminal()
    {
        $this->markTestSkipped();
        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'otp';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testHeadlessOtpAuthenticationNotSupported()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'otp';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    protected function doS2SOtpSubmitCallback(array $content, string $otp)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $content['razorpay_payment_id'] . '/otp/submit',
            'content' => [
                'otp' => $otp
            ],
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function mockOtpElf()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                $payment = $this->getEntityById('payment', $input['payment_id'], true);

                $req = [
                    'Message' => [
                        'PAReq' => [
                            'Merchant' => [
                                'acqBIN' => '11111111111',
                                'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                            ],
                            'CH' => [
                                'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                            ],
                            'Purchase' => [
                                'amount' => '500.00',
                                'xid'    => base64_encode(str_pad($input['payment_id'], 20, '0', STR_PAD_LEFT)),
                                'purchAmount' => '50000',
                                'currency' => '356',
                                'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                'exponent' => 2,
                            ]
                        ]
                    ]
                ];

                $content['Message']['@attributes']['id'] = $payment['public_id'];
                $content['Message']['PARes'] = (new \RZP\Gateway\Mpi\Blade\Mock\Response\Pareq('route'))->enrolledValidResponse($req);

                $xml = base64_encode(\Lib\Formatters\Xml::create('ThreeDSecure', $content));

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'submit_otp',
                        'data'   => [
                            'PaRes' => $xml,
                            'MD' => $input['payment_id']
                        ]
                    ]
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);
    }
}
