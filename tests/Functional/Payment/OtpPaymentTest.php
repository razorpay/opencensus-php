<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use Cache;
use Crypt;
use Mockery;

use RZP\Exception;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayRequestException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Terminal\Options as TerminalOptions;
use RZP\Models\Card\IIN;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\OtpElf;

class OtpPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $otpFlow = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OtpPaymentTestData.php';

        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                if ($feature === 'save_all_cards' or $feature === 'use_edge_passport_for_auth' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                                {
                                    return 'off';
                                }

                                if ($feature === 'disable_rzp_tokenised_payment')
                                {
                                    return 'off';
                                }

                                return 'on';
                            }));
    }

    public function testIvrAuthenticationPayment()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['ivr']);
        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
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
        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testIvrAuthenticationPaymentFailure()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['ivr']);
        $this->mockCardVault();

       $this->mockServerContentFunction(
            function(& $content, $action)
            {
                throw new GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE
                );

            }, Gateway::MPI_BLADE
        );
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
            },
            GatewayErrorException::class);

        $iin = $this->getEntityById('iin', 556763, true);
        self::assertNotContains('ivr', $iin['flows']);
    }

    public function testIvrAuthenticationPaymentFailurePreferredAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['ivr','otp_auth_default']);
        $this->mockCardVault();

       $this->mockServerContentFunction(
            function(& $content, $action)
            {
                throw new GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE
                );

            }, Gateway::MPI_BLADE
        );
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $this->setOtp('213433');

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
            },
            GatewayErrorException::class);

        $iin = $this->getEntityById('iin', 556763, true);
        self::assertNotContains('ivr', $iin['flows']);
    }

    public function testIvrAuthenticationPaymentWithHeadlessFeature()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['ivr_disable']);

        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testIvrAuthenticationPaymentWHeadless()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['ivr']);
        $this->mockCardVault();
        $this->mockOtpElf(function (array $input)
        {
            self::assertTrue(false);
        });

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'otp'          => '1',
                'ivr'          => '1',
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
        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testIvrOtpCreatePaymentS2SJsonFlowResponse()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertNotNull($content['razorpay_payment_id']);

        $this->assertArrayHasKey('next', $content);
        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
    }

    public function testIvrOtpCreatePaymentS2SJsonV2FlowResponse()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json','json_v2']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));
    }

    public function testIvrOtpAuthenticationPaymentS2SJsonFlowWithOtpAuthType()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        /******** OTP Submit Call *******/
        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        /******** Redirect Call *******/
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $paymentId = explode('/', $content['next'][2]['url'])[5];

        $response = $this->makeRedirectTo3ds($this->getPaymentRedirectTo3dsUrl($paymentId));

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

    }

    public function testIvrOtpAuthenticationPaymentS2SJsonFlowWithOtpAuthDefaultFeature()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json', 'otp_auth_default']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        /******** OTP Submit Call *******/
        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        /******** Redirect Call *******/
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $paymentId = explode('/', $content['next'][2]['url'])[5];

        $response = $this->makeRedirectTo3ds($this->getPaymentRedirectTo3dsUrl($paymentId));

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

    }

    public function testIvrOtpAuthenticationPaymentS2SJsonV2FlowWithAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json', 'otp_auth_default','json_v2']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        /********* OTP Generate Call ***********/
        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpResendUrlJson($content['next'][1]['url']));

        /********* OTP Submit Call ***********/
        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);


        /********* Redirect Call ***********/

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

    }

    public function testIvrHeadlessOtpCreatePaymentS2SJsonFlowResponse()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertNotNull($content['razorpay_payment_id']);

        $this->assertArrayHasKey('next', $content);
        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
    }

    public function testIvrHeadlessOtpCreatePaymentS2SJsonV2FlowResponse()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json','json_v2']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));
    }

    public function testIvrHeadlessOtpAuthenticationPaymentS2SJsonFlow()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json', 'otp_auth_default']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        /******** OTP Submit Call *******/
        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        /******** Redirect Call *******/
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $paymentId = explode('/', $content['next'][2]['url'])[5];

        $response = $this->makeRedirectTo3ds($this->getPaymentRedirectTo3dsUrl($paymentId));

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

    }

    public function testIvrHeadlessOtpAuthenticationPaymentS2SJsonV2FlowWithAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures((['s2s', 's2s_json', 'otp_auth_default','json_v2']));

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        /********* OTP Generate Call ***********/
        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpResendUrlJson($content['next'][1]['url']));

        /********* OTP Submit Call ***********/
        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);


        /********* Redirect Call ***********/

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

    }

    public function testHeadlessOtpAuthenticationPayment()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $this->mockCardVault();
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

    public function testSubmitOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring'     => '1',
                    'recurring_3ds'     => '1',
                    'recurring_non_3ds' => '1'
                ]
            ]);

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $this->gateway = 'hitachi';


        $this->fixtures->merchant->addFeatures(['otpelf']);

        $this->mockCardVault();
        $this->mockSubmitOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'    => '1',
                'headless_otp' => '1',
            ],
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;
        $payment['auth_type'] = 'otp';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicLiveAuth();

        $content = $this->makeRequestAndGetContent($request);

        self::assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testHeadlessOtpAuthenticationPaymentFailed()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $this->mockCardVault();

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
        GatewayErrorException::class);
    }

    public function testHeadlessOtpAuthenticationPaymentWithout3ds()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);


        $this->mockCardVault();
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

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
        $otpelf = $this->mockOtpElf();

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::on(function ($argument)
            {
                // Ensure 'client' is sent from api side and that
                // the ip address is the same as was passed in the s2s request
                if ($argument['client']['ip'] === '52.34.123.23')
                {
                    return true;
                }

                return false;
            }))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => true,
                    'data' => [
                        'action' => 'page_resolved',
                        'data'   => [
                            'type' => 'otp',
                            'bank' => 'ICIC',
                            'next' => [
                                'submit_otp',
                                'resend_otp',
                            ]
                        ]
                    ]
                ];
            });;

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

        $payment['ip']         = '52.34.123.23';
        $payment['user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

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

    public function testHeadlessOtpAuthenticationPaymentS2SRedirectFlow()
    {
        $this->fixtures->create('gateway_rule', [
            'method'        => 'card',
            'merchant_id'   => '100000Razorpay',
            'gateway'       => 'first_data',
            'type'          => 'sorter',
            'filter_type'   => 'select',
            'min_amount'    => 0,
            'load'          => 100,
            'group'         => null,
            'currency'      => 'INR',
            'step'          => 'authorization',
        ]);

        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

       $this->fixtures->create('terminal:direct_first_data_recurring_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        TerminalOptions::setTestChance(500);

        $this->app->razorx->method('getTreatment')
                        ->will($this->returnCallback(
                            function ($mid, $feature, $mode) {
                                if ($feature === 'redirect_terminal_cache')
                                {
                                    return 'on';
                                }
                                return 'off';
                            }));

        $this->fixtures->merchant->addFeatures(['s2s', 'otp_auth_default']);
        $this->mockCardVault();
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $payment = $this->getDbLastEntity('payment');

        $key = $payment->getCacheRedirectInputKey();

        $data = Cache::get($key);

        $this->assertArrayHasKey('gateway_input', $data);

        $gatewayInput =  $data['gateway_input'];

        $this->assertArrayHasKey('selected_terminals_ids', $gatewayInput);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $response = $this->makeRedirectToAuthorize($targetUrl);

        $content = $this->getJsonContentFromResponse($response, null);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('first_data', $payment['gateway']);
        self::assertEquals('FDRcrDTrmnl3DS', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
        assertTrue($this->otpFlow);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonRedirectFlow()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default']);
        $this->mockCardVault();
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getDbLastEntity('payment');

        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
                'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $paymentId = explode('/', $content['next'][2]['url'])[5];

        $response = $this->makeRedirectTo3ds($this->getPaymentRedirectTo3dsUrl($paymentId));

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonExtendedFlow()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 'json_v2']);

        $this->mockCardVault();

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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpResendUrlJson($content['next'][1]['url']));

        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonExtendedFlowVaultDown()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 'json_v2']);

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $this->count = 0;

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_VAULT_TOKENIZE_FAILED);

            });

        $cardVault->shouldReceive('encrypt')
            ->with(Mockery::type('array'))
            ->andReturnUsing(function ($input)
            {
                return base64_encode($input['card']);
            });


        $cardVault->shouldReceive('decrypt')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($token)
            {
                return base64_decode($token);
            });

        $this->app->instance('card.cardVault', $cardVault);

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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);

        $card = $this->getLastEntity('card',true);

        self::assertNull($card['vault_token']);
        self::assertNull($card['vault']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonExtendedFlowOtpGenerateFailure()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 'json_v2']);

        $this->mockCardVault();

        $otpelf = $this->mockOtpElf();

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => 'UNSERVICEABLE_PAGE',
                        ''
                    ],
                ];
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $response = $this->makeRequestParent($request);
        },
        GatewayErrorException::class);

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonExtendedFlowOtpGenerateFailureNonHeadless()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 'json_v2']);

        $this->mockCardVault();

        $otpelf = $this->mockOtpElf();

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => 'UNSERVICEABLE_PAGE',
                        ''
                    ],
                ];
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $flows = [
            '3ds'          => '1',
            'headless_otp' => '0',
        ];

        $this->fixtures->edit('iin', 556763, ['flows' => $flows]);

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $response = $this->makeRequestParent($request);
        },
        BadRequestException::class);

        $response = $this->makeRedirectToAuthorize($content['next'][0]['url']);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonExtendedFlowOtpResend()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 'json_v2']);

        $this->mockCardVault();

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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('redirect', $content['next'][0]['action']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_generate', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpGenerateUrlPublic($content['next'][1]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlJson($content['next'][1]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrl($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpResendUrlJson($content['next'][1]['url']));

        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonRedirectFlow3dsFallback()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:direct_first_data_recurring_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default']);
        $this->mockCardVault();
        $otpelf = $this->mockOtpElf();

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => 'UNSERVICEABLE_PAGE',
                        ''
                    ],
                ];
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertNull($payment['auth_type']);
        self::assertEquals('first_data', $payment['gateway']);
        self::assertEquals('FDRcrDTrmnl3DS', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SRedirectFlowGetSecretBugFix()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        TerminalOptions::setTestChance(500);

        $this->app->razorx->method('getTreatment')
                        ->will($this->returnCallback(
                            function ($mid, $feature, $mode) {
                                if ($feature === 'redirect_terminal_cache')
                                {
                                    return 'on';
                                }
                                return 'off';
                            }));

        $this->fixtures->merchant->addFeatures(['s2s', 'otp_auth_default']);
        $this->mockCardVault();
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

        $order = $this->fixtures->create('order', ['id' => '100000000order', 'amount' => 50000]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['order_id'] = 'order_100000000order';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $payment = $this->getDbLastEntity('payment');

        $key = $payment->getCacheRedirectInputKey();

        $data = Cache::get($key);

        $this->assertArrayHasKey('gateway_input', $data);

        $gatewayInput =  $data['gateway_input'];

        $this->assertArrayHasKey('selected_terminals_ids', $gatewayInput);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $response = $this->makeRedirectToAuthorize($targetUrl);

        $content = $response->original;

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('authorized', $payment['status']);
        assertTrue($this->otpFlow);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SDoubleRedirectOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => 'PAYMENT_TIMEOUT'
                    ],
                ];
            });

        $this->fixtures->merchant->addFeatures(['s2s', 'otp_auth_default']);
        $this->mockCardVault();

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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $url =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($url));

        $this->makeRequestAndCatchException(
        function() use ($url)
        {
            $this->makeRedirectToAuthorize($url);
        },
        GatewayRequestException::class,
        'Gateway request timed out');

        $this->mockOtpElf();

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['status' => 'created']);

        $this->makeRedirectToAuthorize($url);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $payment2['id']);
        $this->assertEquals('authorized',   $payment2['status']);
        $this->assertEquals('headless_otp', $payment2['auth_type']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SDoubleRedirect3ds()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->count = 0;
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if (($this->count === 0) and
                ($action === 'authenticate'))
            {
                $this->count = 1;
                throw new GatewayTimeoutException('Timed out', null, true);
            }
        }, 'mpi_blade');

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->mockCardVault();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $this->makeRequestAndCatchException(
        function() use ($targetUrl)
        {
            $this->makeRedirectToAuthorize($targetUrl);
        },
        GatewayRequestException::class,
        'Gateway request timed out');

        $this->mockOtpElf();

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['status' => 'created']);

        $this->makeRedirectToAuthorize($targetUrl);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $payment2['id']);
        $this->assertEquals('authorized',   $payment2['status']);
        $this->assertNull($payment2['auth_type']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SRedirect3dsCallbackUrl()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->count = 0;
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if (($this->count === 0) and
                ($action === 'authenticate'))
            {
                $this->count = 1;
                throw new GatewayTimeoutException('Timed out', null, true);
            }
        }, 'mpi_blade');

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->mockCardVault();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
            ],
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['callback_url'] = 'https://google.com';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $id = getTextBetweenStrings($targetUrl, '/payments/', '/authenticate');

        $this->redirectToAuthorize = true;

        $url = $this->getPaymentRedirectToAuthorizrUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $this->app['env'] = 'dev';

        $this->app['config']->set('app.debug', false);

        $response = $this->makeRequestParent($request);

        $request = $this->getFormRequestFromResponse($response->getContent(), $url);

        $this->assertEquals('https://google.com', $request['url']);
        $this->assertEquals('The gateway request to submit payment information timed out. Please submit your details again', $request['content']['error[description]']);
        $this->assertEquals('GATEWAY_ERROR', $request['content']['error[code]']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SHtmlView()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
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
        $content = json_decode($response->getContent(), true);

        self::assertArrayHasKey('next', $content);
        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertNotNull($content['razorpay_payment_id']);

        $this->fixtures->merchant->removeFeatures(['s2s_otp_json']);

        $response = $this->makeRequestParent($request);
        $content = json_decode($response->getContent(), true);
        self::assertNull($content);

        $response = $this->doS2sPrivateAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpResendPaymentS2S()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
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

    public function testOtpPreferredAuthPaymentWithHeadlessDisable()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['headless_disable']);

        $this->mockCardVault();
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

        $this->mockCardVault();
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

        $this->mockCardVault();
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

        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function() use($conn)
             {
                return $conn;
             });

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


        $this->mockCardVault();
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


        $this->mockCardVault();
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


        $this->mockCardVault();
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

        $this->mockCardVault();
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

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);
        $this->mockCardVault();

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

    public function testIciciOtpAuth()
    {
        $this->fixtures->create('terminal:icici', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'otp' => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');
        $this->doAuthPayment($payment);
        $payment= $this->getDbLastEntity('payment');
        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('otp', $payment['auth_type']);
    }

    public function testExpressPayPreferredAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);
        $this->mockCardVault();

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

    public function testExpressPay3dsflow()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);
        $this->mockCardVault();

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
        $payment['preferred_auth'] = ['3ds', 'otp'];
        // $this->setOtp('213433');
        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        self::assertNull($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hdfc', $payment['gateway']);
    }

    public function testExpressPayOtpResend()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['axis_express_pay', 's2s_otp_json']);
        $this->mockCardVault();

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

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $this->mockCardVault();

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
            'terminal_id'       => ltrim($this->getLastEntity('terminal', true)['id'], 'term_'),
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


        $this->mockCardVault();

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
                            'reason' => $otpElfError,
                            'fatal'  => true,
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
            GatewayErrorException::class);

            $iin = $this->getEntityById('iin', 556763, true);

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals('headless_otp', $payment['auth_type']);

            self::assertNotContains('headless_otp', $iin['flows']);
        }
    }

    public function testHeadlessOtpAuthenticationPaymentFailedDisableIinSubmitOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $this->mockCardVault();

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

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => "PAGE_UNKNOWN",
                        'fatal'  => true,
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
        GatewayErrorException::class);

        $iin = $this->getEntityById('iin', 556763, true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('headless_otp', $payment['auth_type']);

        self::assertNotContains('headless_otp', $iin['flows']);

        // preferred auth
        $this->fixtures->edit('iin', 556763, ['flows' => $flows]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = 'otp';

        $this->setOtp('213433');

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        GatewayErrorException::class);

        $iin = $this->getEntityById('iin', 556763, true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('headless_otp', $payment['auth_type']);

        self::assertNotContains('headless_otp', $iin['flows']);
    }

    public function testHeadlessOtpAuthenticationPaymentFailedDisableIinResendOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();

        $otpelf = $this->mockOtpElf();

        $otpelf->shouldReceive('otpResend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => "PAGE_UNKNOWN",
                        'fatal'  => true,
                    ],
                ];
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
            $response = $this->doS2SOtpResend($content);
        },
        GatewayErrorException::class);

        $iin = $this->getEntityById('iin', 556763, true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('headless_otp', $payment['auth_type']);

        self::assertNotContains('headless_otp', $iin['flows']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SInvalidOtp()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s','s2s_otp_json']);
        $this->mockCardVault();
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
                        'reason' => 'INVALID_OTP'
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

    public function testHeadlessOtpAuthenticationPaymentS2SInvalidOtpCardBlocked()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                    'error' => [
                        'reason' => 'CARD_BLOCKED'
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

        //using double quotes since we are using regex for new line
        $this->makeRequestAndCatchException(
            function() use ($content)
            {
               $this->doS2SOtpSubmitCallback($content, '123456');
            },
            GatewayErrorException::class,
            "Payment processing failed because cardholder's card was blocked\n".
            "Gateway Error Code: \n".
            "Gateway Error Desc: ");

    }

    public function testHeadlessOtpAuthenticationPaymentS2SInvalidOtpUnknownError()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s','s2s_otp_json']);
        $this->mockCardVault();
        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                    'error' => [
                        'reason' => 'UNKNOWN_ERROR'
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

        //using double quotes since we are using regex for new line
        $this->makeRequestAndCatchException(
            function() use ($content)
            {
               $this->doS2SOtpSubmitCallback($content, '123456');
            },
            GatewayErrorException::class,
            "Payment failed\n".
            "Gateway Error Code: \n".
            "Gateway Error Desc: ");

    }

    public function testHeadlessOtpAuthenticationPaymentS2SFailure()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
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

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->mockCardVault();
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

    public function testHeadlessOtpDefaultAuthType3ds()
    {
        $this->fixtures->create('gateway_rule', [
            'method'        => 'card',
            'merchant_id'   => '100000Razorpay',
            'gateway'       => 'first_data',
            'type'          => 'sorter',
            'filter_type'   => 'select',
            'min_amount'    => 0,
            'load'          => 100,
            'group'         => null,
            'currency'      => 'INR',
            'step'          => 'authorization',
        ]);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:direct_first_data_recurring_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default',]);
        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        // self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('first_data', $payment['gateway']);
        self::assertEquals('FDRcrDTrmnl3DS', $payment['terminal_id']);
    }

    public function testHeadlessOtpDefaultAuthTypeFallback()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default',]);
        $this->mockCardVault();

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

        $this->fixtures->merchant->addFeatures(['otp_auth_default',]);
        $this->mockCardVault();

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
        GatewayErrorException::class);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SErrorInOtpElf()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();
        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
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

        $this->setOtp('213433');

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        GatewayErrorException::class);
    }

    public function testHeadlessRedirect()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->mockCardVault();
        $this->mockOtpElf();
        $this->setRedirectTo3ds(true);
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

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessRedirectSharedTerminalFilter()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                    'non_recurring' => '1',
            ]]);

        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->mockCardVault();
        $this->mockOtpElf();
        $this->setRedirectTo3ds(true);
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

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
    }

    public function testHeadlessRedirectInvalidAuthType()
    {
        $payment = $this->fixtures->create('payment:netbanking_created');

        $this->ba->publicAuth();

        $url = $this->getPaymentRedirectTo3dsUrl($payment->getPublicId());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $url;

        $this->makeRequestAndCatchException(
        function() use ($testData)
        {
            $this->runRequestResponseFlow($testData);
        },
        \RZP\Exception\BadRequestException::class,
        'Payment failed');
    }

    public function testHeadlessRedirectNoInputDetailsInCache()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->mockCardVault();
        $this->mockOtpElf();
        $this->setRedirectTo3ds(true);
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

        $store = Cache::store();

        // TODO remove this after session migration
        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        Cache::shouldReceive('store')
                ->withAnyArgs()
                ->andReturn($store);

        Cache::shouldReceive('put')
            ->andReturnUsing(function($key)
            {
                return true;
            });

        Cache::shouldReceive('get')
            ->andReturnUsing(function() use ($payment)
            {
                return null;
            });

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        \RZP\Exception\BadRequestException::class,
        'The payment has already been processed');
    }

    public function testHeadlessRedirectSaveCard()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->mockCardVault();
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
        $payment['save'] = 1;
        $payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);

        // create payment and fetch entities

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // test haeadless with save card;
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array('cvv' => 111);
        $payment['token'] = $token[Payment::TOKEN];
        $payment[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $this->setOtp(1234);

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);

        // redirect flow
        $this->setRedirectTo3ds(true);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array('cvv' => 111);
        $payment['token'] = $token[Payment::TOKEN];
        $payment[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $this->setOtp(1234);

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpOnRupayCards()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();
        $this->mockOtpElfForRupay();

        $this->fixtures->iin->create([
            'iin'     => '607384',
            'country' => 'IN',
            'issuer'  => 'PUNB',
            'network' => 'RuPay',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('paysecure', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpTimeoutException()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $this->mockCardVault();

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

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                return [
                    'success' => false,
                    'error'   => [
                        'reason' => 'PAYMENT_TIMEOUT'
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
        GatewayRequestException::class,
        'Gateway request timed out');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('headless_otp', $payment['auth_type']);
    }

    public function testMerchantCallbackUrl()
    {
        $this->app['config']->set('app.throw_exception_in_testing', false);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);

        $this->mockCardVault();
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
        $payment['callback_url'] = 'https://google.com';

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

        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        $route = $this->app['api.route'];

        $url = $route->getPublicCallbackUrlWithHash($content['razorpay_payment_id'], 'rzp_test_TheTestAuthKey', 'payment_callback_post');

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [],
        ];

        $this->app['env'] = 'dev';

        $this->app['config']->set('app.debug', false);

        $response = $this->makeRequestParent($request);

        $request = $this->getFormRequestFromResponse($response->getContent(), $url);

        $this->assertEquals('https://google.com', $request['url']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SFirstData()
    {
        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json', 'first_data_s2s_flow']);
        $this->mockCardVault();
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
        self::assertEquals('first_data', $payment['gateway']);
        self::assertEquals('1000FrstDataTl', $payment['terminal_id']);

        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertEquals($content['razorpay_payment_id'], $response['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);
        self::assertEquals('authorized', $payment['status']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SFirstDataRupay()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();
        $this->mockOtpElfForRupay();

        $this->fixtures->iin->create([
            'iin'     => '607384',
            'country' => 'IN',
            'issuer'  => 'PUNB',
            'network' => 'RuPay',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('paysecure', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpAuthenticationPaymentFailedDisableIinPreferredAuth()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);



        $this->mockCardVault();

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
                                'reason' => $otpElfError,
                                'fatal'  => true,
                            ],
                        ];
                    });
            $this->fixtures->edit('iin', 556763, ['flows' => $flows]);
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '5567630000002004';
            $payment['preferred_auth'] = ['otp'];
            $this->setOtp('213433');
            $this->doAuthPayment($payment);
            $iin = $this->getEntityById('iin', 556763, true);
            self::assertNotContains('headless_otp', $iin['flows']);
        }
    }

    public function testOtpPreferredAuthPaymentWithRetryCheck()
    {
        $this->fixtures->create('gateway_rule', [
            'method'        => 'card',
            'merchant_id'   => '100000Razorpay',
            'gateway'       => 'hitachi',
            'type'          => 'sorter',
            'filter_type'   => 'select',
            'min_amount'    => 0,
            'load'          => 100,
            'group'         => null,
            'currency'      => 'INR',
            'step'          => 'authorization',
        ]);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->create('terminal:direct_first_data_recurring_terminal', [
            'type' => [
                'non_recurring' => '1',
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
        $payment['auth_type'] = 'otp';

        TerminalOptions::setTestChance(500);



        $this->mockCardVault();

        $otpelf = $this->mockOtpElf();
        $this->count = 0;

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ()
            {
                if ($this->count === 0)
                {
                    $this->count = 1;
                    return [
                        'success' => false,
                        'error'   => [
                            'reason' => 'UNSERVICEABLE_PAGE'
                        ],
                    ];
                }

                return [
                        'success' => true,
                        'data' => [
                            'action' => 'page_resolved',
                            'data' => [
                                'next' => ['submit_otp', 'resend_otp'],
                                'type' => 'otp'
                            ]]
                        ];
            });

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertTrue($this->otpFlow);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
    }


    public function testOtpPaymentNoAvailbaleActions()
    {
         $this->fixtures->create('terminal:shared_first_data_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json', 'first_data_s2s_flow']);

        $this->mockCardVault();

        $otpelf = \Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                    'error' =>[
                        'reason' => 'NO_AVAILABLE_ACTIONS',
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
        self::assertEquals('first_data', $payment['gateway']);
        self::assertEquals('1000FrstDataTl', $payment['terminal_id']);

        $this->makeRequestAndCatchException(
        function() use ($content)
        {
            $response = $this->doS2SOtpSubmitCallback($content, '123456');
        },
        GatewayErrorException::class);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('failed', $payment['status']);
    }

    public function testRedirectCacheOnRupayCards()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '607384',
            'country' => 'IN',
            'issuer'  => 'PUNB',
            'network' => 'RuPay',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default', 's2s_otp_json']);

        $this->mockCardVault();

        $this->mockOtpElfForRupay();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';


        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $trackId = getTextBetweenStrings($targetUrl, '/payments/', '/authenticate');

        $url = $this->getPaymentRedirectToAuthorizrUrl($trackId);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $response = $this->makeRequestParent($request);

        $payment = $this->getDbLastEntity('payment');

        $key = $payment->getPaymentResponseCacheKey();

        $data = Cache::get($key);

        $this->assertNotNull($data);

        $data =  Crypt::encrypt(['test_data']);;

        Cache::put($key, $data, 120);

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertEquals('test_data', $content[0]);
    }

    public function testHeadlessOtpOnHdfcRupay()
    {
        $this->mockCardVault();
        $this->mockOtpElfForRupay();

        $this->fixtures->iin->create([
            'iin'     => '607384',
            'country' => 'IN',
            'issuer'  => 'PUNB',
            'network' => 'RuPay',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertTrue($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hdfc', $payment['gateway']);
        self::assertEquals('1n25f6uN5S1Z5a', $payment['terminal_id']);
    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonRedirectFlowMultipleRedirectFailure()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default']);
        $this->mockCardVault();
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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getDbLastEntity('payment');


        //For a headless otp payment if we receive multiple redirect requests,
        // In the first request from customer browser native otp page is opened.
        //
        $this->fixtures->edit("payment",$payment['id'],[
            'auth_type' => null
        ]);

        $this->makeRequestAndCatchException(
            function() use ($content)
            {
                $response = $this->doS2SOtpSubmitCallback($content, '123456');
            },
            BadRequestException::class);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('created', $payment['status']);

    }

    public function testHeadlessOtpAuthenticationPaymentS2SJsonOtpResend()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default']);

        $this->mockCardVault();

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

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);

        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('action', $content['next'][2]);
        $this->assertEquals('redirect', $content['next'][2]['action']);
        $this->assertTrue($this->isOtpFallbackUrl($content['next'][2]['url']));

        $url = $this->getUri($content['next'][1]['url']);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);
        $this->assertEquals('otp_submit', $content['next'][0]['action']);
        $this->assertTrue($this->isOtpCallbackUrlPrivate($content['next'][0]['url']));

        $this->assertArrayHasKey('action', $content['next'][1]);
        $this->assertEquals('otp_resend', $content['next'][1]['action']);
        $this->assertTrue($this->isOtpResendUrlPrivate($content['next'][1]['url']));

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $url = $this->getUri($content['next'][0]['url']);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitaDirTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
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

    protected function makePublicAuthRequest($uri, $content)
    {
        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => $content
        ];

        $this->ba->publicAuth();

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
