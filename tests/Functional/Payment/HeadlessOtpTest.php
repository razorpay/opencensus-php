<?php

namespace RZP\Tests\Functional\Payment;

use Redis;

use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayRequestException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Card\IIN;
use RZP\Trace\TraceCode;
use RZP\Services\OtpElf;

class HeadlessOtpTest extends TestCase
{
    use PaymentTrait;

    protected $otpFlow = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/HeadlessTestData.php';

        parent::setUp();
    }

    public function testHeadlessOtpAuthenticationPayment()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
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

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpAuthenticationPaymentFailed()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [];
            });

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

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        GatewayRequestException::class);
    }

    public function testHeadlessOtpAuthenticationPaymentWithout3ds()
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
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authenticate')
            {
                throw new GatewayTimeoutException('Timed out', null, true);
            }
        }, 'mpi_blade');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
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

    public function testHeadlessOtpResendPaymentS2S()
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

        $response = $this->doS2SOtpResend($content);

        $expectedNext = [
            'otp_submit',
            'otp_resend',
        ];

        self::assertEquals($expectedNext, $response['next']);
        self::assertEquals($content['razorpay_payment_id'], $response['razorpay_payment_id']);
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

        self::assertFalse($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testOtpPreferredAuthPaymentWithoutTerminal()
    {
        $this->otpFlow = false;
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

        self::assertFalse($this->otpFlow);
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

        $this->otpFlow = false;
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

        self::assertFalse($this->otpFlow);
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

        $this->otpFlow = false;
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

        self::assertFalse($this->otpFlow);
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

        $this->otpFlow = false;
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
        self::assertTrue($this->otpFlow);
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
                'headless_otp'  => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'otp';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testOtpAuthPaymentWithCardNotSupported()
    {
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
        $payment['auth_type'] = 'otp';

        $this->fixtures->merchant->addFeatures(['otpelf']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testExpressPayOtpAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);
        $this->mockTokenEx();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');
        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);

        $payment = $this->getLastEntity('mpi', true);

        self::assertEquals('mpi_enstage', $payment['gateway']);
    }

    public function testExpressPayPreferredAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);
        $this->mockTokenEx();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['otp'];

        $this->setOtp('213433');
        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);

        $payment = $this->getLastEntity('mpi', true);

        self::assertEquals('mpi_enstage', $payment['gateway']);
    }

    public function testExpressPayOtpResend()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);
        $this->mockTokenEx();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $data = [];

        $data['request'] = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment,
        ];

        $data['response'] = [
            'content'   => []
        ];

        $this->ba->publicAuth();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['status' => 'created']);

        $data = [
            'next' => [
                'otp_submit',
                'otp_resend',
            ],
            'razorpay_payment_id' => $payment['id'],
        ];

        // @codingStandardsIgnoreLine
        $response = $this->doS2SOtpResend($data);

        $this->assertContains('otp_submit', $response['next']);
        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);
    }

    public function testExpressPayOtpResendWithoutOtpGenerate()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);

        $this->mockTokenEx();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->create('card',[
            'id'                => '100000000Acard',
            'merchant_id'       => $this->getLastEntity('merchant', true)['id'],
            'name'              => 'test',
            'expiry_month'      => '12',
            'expiry_year'       => '2100',
            'iin'               => '556763',
            'last4'             => '2004',
        ]);

        $payment = $this->fixtures->create('payment:status_created', [
            'card_id'           => substr($this->getLastEntity('card', true)['id'], 5),
            'terminal_id'       => $this->getLastEntity('terminal',true)['id'],
            'gateway'           => 'hitachi',
        ]);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->create('mpi', [
            'payment_id'    => substr($payment['id'], 4),
            'amount'        => $payment['amount'],
            'action'        => 'authorize',
        ]);

        $data = [
            'next' => [
                'otp_submit',
                'otp_resend',
            ],
            'razorpay_payment_id' => $payment['id'],
        ];

        $this->makeRequestAndCatchException(
        function() use ($data)
        {
            // @codingStandardsIgnoreLine
            $this->doS2SOtpResend($data);
        },
        \RZP\Exception\LogicException::class,
        'Gateway does not support OTP resend');
    }

    public function testHeadlessOtpAuthenticationPaymentFailedDisableIin()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf']);
        $this->mockTokenEx();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
        ]);

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'magic'        => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        foreach (OtpElf::$otpElfErrors as $otpElfError)
        {
            $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

            $this->app->instance('card.otpelf', $otpelf);

            $otpelf->shouldReceive('otpSend')
                ->with(\Mockery::type('array'))
                ->andReturnUsing(function () use ($otpElfError)
                {
                    return [
                        'success' => false,
                        'error'   => [
                            'reason' => $otpElfError
                        ],
                    ];
                });

            $this->fixtures->edit('iin', 556763, ['flows' => $flows]);

            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '5567630000002004';
            $payment['auth_type'] = 'otp';

            $this->setOtp('213433');

            $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            GatewayRequestException::class);

            $iin = $this->getEntityById('iin', 556763, true);

            self::assertNotContains('headless_otp', $iin['flows']);
        }
    }

    public function testHeadlessOtpAuthenticationPaymentS2SInvalidOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 's2s']);
        $this->mockTokenEx();
        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => true,
                    'data' => [
                        'action' => 'page_resolved',
                        'data' => [
                            'next' => ['submit_otp', 'resend_otp'],
                        ],
                    ],
                    'error' => [
                        "reason" => 'INVALID_OTP'
                    ],
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);

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

        $this->makeRequestAndCatchException(
            function() use ($content)
            {
                $this->doS2SOtpSubmitCallback($content, '123456');
            },
            BadRequestException::class);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SFailure()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 's2s']);
        $this->mockTokenEx();
        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);

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

        $this->makeRequestAndCatchException(
            function() use ($content)
            {
                $this->doS2SOtpSubmitCallback($content, '123456');
            },
            GatewayErrorException::class);
    }

    public function testHeadlessOtpDefaultAuthType()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'otp_auth_default']);
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

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpDefaultAuthTypeFallback()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'otp_auth_default']);
        $this->mockTokenEx();

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => true,
                    'data' => [
                        'action' => ''
                    ],
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);

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

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertFalse($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpDefaultAuthTypeNoFallback()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otpelf', 'otp_auth_default']);
        $this->mockTokenEx();

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => true,
                    'data' => [
                        'action' => ''
                    ],
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);

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

        // since auth type is set there should be no fallback.
        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        GatewayRequestException::class);
    }

    // @codingStandardsIgnoreLine
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

    // @codingStandardsIgnoreLine
    protected function doS2SOtpResend(array $content)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $content['razorpay_payment_id'] . '/otp/resend',
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
