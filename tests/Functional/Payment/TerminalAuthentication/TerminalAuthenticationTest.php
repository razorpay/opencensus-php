<?php

namespace RZP\Tests\Functional\Payment\TerminalAuthenitcation;

use Redis;

use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\AuthType;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Capability;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Terminal\Options as TerminalOptions;
use RZP\Models\Terminal\AuthenticationTerminals as AuthTerminals;

class TerminalAuthenticationTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalAuthenticationTestData.php';

        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                if ($feature === 'save_all_cards' or  $feature === 'store_empty_value_for_non_exempted_card_metadata')
                                {
                                    return 'off';
                                }
                                return 'on';
                            }));
    }

    // boost 3ds over headless otp
    public function testAuthenticationGateway3ds()
    {
        TerminalOptions::setTestChance(200);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

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
                'headless_otp' => '1'
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
        self::assertFalse($this->otpFlow);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testCreateCardPaymentJsonRouteIvrFallbackTo3ds()
    {
        $this->enableCpsConfig();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 100;
        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin'     => '457392',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'ivr', 'otp_auth_default']);

        $response = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testAuthenticationGatewayHeadlessOtp()
    {

        TerminalOptions::setTestChance(20000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

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
                'headless_otp' => '1'
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
        self::assertEquals('headless_otp' ,$payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayMigsHeadlessOtp()
    {
        TerminalOptions::setTestChance(20000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $this->fixtures->create('terminal:shared_axis_terminal', ['capability' => 2]);

        $this->fixtures->iin->create([
            'iin' => '556763',
            'country' => 'IN',
            'issuer' => 'ICIC',
            'network' => 'MasterCard',
            'flows' => [
                '3ds' => '1',
                'headless_otp' => '1'
            ]
        ]);

        $this->otpFlow = false;
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds', 'otp'];

        $this->mockCardVault();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment, null);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertTrue($this->otpFlow);
        self::assertEquals('headless_otp' ,$payment['auth_type']);
        self::assertEquals('axis_migs', $payment['gateway']);
        self::assertEquals('1000AxisMigsTl', $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayIvr()
    {
        TerminalOptions::setTestChance(80000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

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
        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayExpressPay()
    {
        TerminalOptions::setTestChance(80000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);
        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
                'headless_otp' => '1',
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

        $mpi = $this->getLastEntity('mpi', true);

        self::assertEquals('mpi_enstage', $mpi['gateway']);
        $this->assertEquals('mpi_enstage', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    // boost 3ds over headless otp
    public function testAuthenticationGatewayPin()
    {
        TerminalOptions::setTestChance(20000);

        Config(['app.data_store.mock' => false]);
        // Mocking mutex since we are mocking redis and partial mock
        // is difficult to mock (read as doesn't work) in laravel
        config(['services.mutex.mock' => true]);

        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function() use ($conn)
             {
                return $conn;
             });

        Redis::shouldReceive('zrevrange')
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'card_fss'    => '60',
                    'hdfc'        => '50',
                ];
            });

        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'CBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];
        $payment['bank']           = 'CBIN';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('pin', $payment['auth_type']);
        $this->assertEquals('card_fss', $payment['gateway']);
        $this->assertEquals('SharedFssTrmnl', $payment['terminal_id']);
        $this->assertNull($payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    // boost 3ds over headless otp
    public function testAuthenticationGateway3dsAndHeadless()
    {
        TerminalOptions::setTestChance(1000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

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
                'headless_otp' => '1'
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
        self::assertFalse($this->otpFlow);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    // boost cyber source mpi gateway
    public function testAuthenticationGatewayCyberSource()
    {
        TerminalOptions::setTestChance(1000);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', [
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

        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayFirstdata()
    {
        TerminalOptions::setTestChance(1000);

        $this->otpFlow = false;

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();

        $this->mockOtpElf();

        $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds'];

        $response = $this->doAuthPayment($payment);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);

        $this->assertEquals('mpi_blade', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayPaysecure()
    {
        $this->markTestSkipped();
        TerminalOptions::setTestChance(1000);

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');

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

        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '6073849700004947',
            'name'              => 'Test user',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        $this->otpFlow = false;

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';
        $payment['preferred_auth'] = ['3ds'];

        $this->mockCardVault();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);

        $this->assertEquals('paysecure', $payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayHdfcCapabilityFilter()
    {
        TerminalOptions::setTestChance(1000);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', [
            'type' => [
                'non_recurring' => '1',
            ],
            'capability' => 0,
        ]);

        $this->fixtures->iin->create([
            'iin' => '556763',
            'country' => 'IN',
            'issuer' => 'ICIC',
            'network' => 'MasterCard',
            'flows' => [
                '3ds' => '1',
            ]
        ]);

        $this->otpFlow = false;

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds', 'otp'];


        $this->mockCardVault();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);

        self::assertEquals('hdfc', $payment['gateway']);

        $this->assertNull($payment[Payment\Entity::AUTHENTICATION_GATEWAY]);
    }

    public function testAuthenticationGatewayHdfcAuthCapabilityFilter()
    {
        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'type' => [
                'non_recurring' => '1',
            ],
            'capability' => 2
        ]);

        $cardArray = [
            'number'        => '4012001036275556',
            'expiry_month'  => '1',
            'expiry_year'   => '2035',
            'cvv'           => '123',
            'network'       => 'Visa',
            'issuer'        => 'HDFC',
            'name'          => 'Test',
            'international' => false,
        ];

        $card = (new Card\Entity)->fill($cardArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find('10000000000000');
        $terminal = Terminal\Entity::find('1000HdfcShared');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($terminal);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $expectedTerminal = [
            AuthTerminals::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            AuthTerminals::GATEWAY                   => Gateway::HDFC,
            AuthTerminals::CAPABILITY                => Capability::AUTHORIZE,
            AuthTerminals::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            AuthTerminals::AUTH_TYPE                 => AuthType::_3DS,
            AuthTerminals::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ];

        TerminalOptions::setTestChance(0);

        $paymentAuthSelect = new Terminal\AuthSelector($input);

        $authTerminal = $paymentAuthSelect->select();

        $this->assertEquals($expectedTerminal, $authTerminal);

        TerminalOptions::setTestChance(900);

         $expectedTerminal = [
            AuthTerminals::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            AuthTerminals::GATEWAY                   => Gateway::HDFC,
            AuthTerminals::AUTHENTICATION_GATEWAY    => null,
            AuthTerminals::AUTH_TYPE                 => AuthType::_3DS,
            AuthTerminals::GATEWAY_AUTH_TYPE         => null,
        ];

        $paymentAuthSelect = new Terminal\AuthSelector($input);

        $authTerminal = $paymentAuthSelect->select();

        $this->assertEquals($expectedTerminal, $authTerminal);
    }

    public function testAuthenticationGatewayEmi()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'type' => [
                'non_recurring' => '1',
            ],
            'capability' => 2
        ]);

        $this->fixtures->merchant->enableEmi();

        $card = [
            'number'       => '41476700000006',
            'name'         => 'Harshil',
            'expiry_month' => '12',
            'expiry_year'  => '2024',
            'cvv'          => '566'
        ];

        $cardEntity = (new Card\Entity)->fill($card);

        $payment = $this->getDefaultPaymentArrayNeutral();

        $attributes = [
            'amount'       => '300000',
            'method'       => 'emi',
            'emi_duration' => '9',
            'bank'         => 'ICIC',
        ];

        $payment = array_merge($payment, $attributes);

        $payment['token'] = '10000cardtoken';

        $payment['customer_id'] = 'cust_100000customer';

        unset($payment['card']);

        $card = (new Card\Entity)->fill($card);

        $payment = (new Payment\Entity)->fill($payment);

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find('10000000000000');
        $terminal = Terminal\Entity::find('1000HdfcShared');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($terminal);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $expectedTerminal = [
            AuthTerminals::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            AuthTerminals::GATEWAY                   => Gateway::HDFC,
            AuthTerminals::CAPABILITY                => Capability::AUTHORIZE,
            AuthTerminals::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            AuthTerminals::AUTH_TYPE                 => AuthType::_3DS,
            AuthTerminals::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ];

        TerminalOptions::setTestChance(0);

        $paymentAuthSelect = new Terminal\AuthSelector($input);

        $authTerminal = $paymentAuthSelect->select();

        $this->assertEquals($expectedTerminal, $authTerminal);
    }

    // boost 3ds over headless otp
    public function testAuthenticationGateway3dsJsonFlowS2S()
    {
        TerminalOptions::setTestChance(200);

        $this->createGatewayRules($this->testData['testAuthenticationGateway3ds']);

        $this->fixtures->merchant->addFeatures(['s2s','s2s_json', 's2s_otp_json', 'otp_auth_default']);

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
                'headless_otp' => '1'
            ]
        ]);

        $this->otpFlow = false;
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds', 'otp'];

        $this->mockCardVault();
        $this->mockOtpElf();

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
        self::assertEquals('hitachi', $payment['gateway']);
        self::assertEquals('100HitachiTmnl', $payment['terminal_id']);
        self::assertEquals('authorized', $payment['status']);
    }

    protected function createGatewayRules($rules)
    {
        foreach ($rules as $rule)
        {
           $this->fixtures->create('gateway_rule', $rule);
        }
    }
}
