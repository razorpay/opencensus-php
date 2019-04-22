<?php

namespace RZP\Tests\Functional\Payment\TerminalAuthenitcation;

use Redis;

use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Terminal\Options as TerminalOptions;

class TerminalAuthenticationTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
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
                                if ($feature === 'save_all_cards')
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

        $this->fixtures->merchant->addFeatures(['headless']);
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

        $this->fixtures->merchant->addFeatures(['headless']);
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

        $this->fixtures->merchant->addFeatures(['axis_express_pay', 'headless']);
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

        $payment = $this->getLastEntity('mpi', true);

        self::assertEquals('mpi_enstage', $payment['gateway']);
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
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('pin', $payment['auth_type']);
        $this->assertEquals('card_fss', $payment['gateway']);
        $this->assertEquals('SharedFssTrmnl', $payment['terminal_id']);
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

        $this->fixtures->merchant->addFeatures(['headless']);
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

        TerminalOptions::setTestChance(200);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds', 'otp'];

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

        $this->fixtures->merchant->addFeatures(['headless']);
        $this->mockCardVault();
        $this->mockOtpElf();

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

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
