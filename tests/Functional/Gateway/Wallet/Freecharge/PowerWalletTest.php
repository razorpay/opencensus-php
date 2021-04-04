<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PowerWalletTest extends TestCase
{
    use PaymentTrait;

    const LOCAL_CUSTOMER = '100000customer';
    const GLOBAL_CUSTOMER = '10000gcustomer';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FreechargeGatewayTestData.php';

        parent::setUp();

        // Use Freecharge Power Wallet for tests
        // TODO: Test PowerWallet Flow for other wallets
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_freecharge_terminal');

        $this->fixtures->merchant->enableWallet(Account::TEST_ACCOUNT, Wallet::FREECHARGE);

        $this->ba->publicAuth();

        $this->setUpAutoDebitFeature();
    }

    public function testPowerWalletPayment()
    {
        $this->setUpWalletToken();

        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        $payment = $this->doAuthAndGetPayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(empty($wallet['reference1']));
    }

    public function testUserNotAuthenticated()
    {
        // Flow should go through otpGenerate
        $this->setUpWalletToken();

        $payment = $this->doAuthAndGetPayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(isset($wallet['reference1']));
    }

    public function testUserWalletTokenDoesNotExist()
    {
        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        $payment = $this->doAuthAndGetPayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(isset($wallet['reference1']));
    }

    public function testInsufficientBalancePayment()
    {
        $this->setUpWalletToken();

        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        // Mock will return Insufficient Balance
        $this->setOtp(Otp::INSUFFICIENT_BALANCE);

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 100000;

        $data = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });

        return $response;
    }

    public function testUserWalletTokenExpired()
    {
        $now = Carbon::now();

        $tokenAttributes = [
            'method'        => 'wallet',
            'wallet'        => Wallet::FREECHARGE,
            'token'         => '101wallettoken',
            'gateway_token' => '8c31d80b-83ed-4f52-8377-71301790ccaa',
            'customer_id'   => self::GLOBAL_CUSTOMER,
            'terminal_id'   => $this->sharedTerminal->getId(),
            'created_at'    => $now->timestamp,
            'expired_at'    => $now->subYear()->timestamp,
        ];

        // Create Token and AppToken
        $token = $this->fixtures->create('token', $tokenAttributes);

        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        $payment = $this->doAuthAndGetPayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(isset($wallet['reference1']));
    }

    public function testInvalidGatewayToken()
    {
        $now = Carbon::now();

        $tokenAttributes = [
            'method'        => 'wallet',
            'wallet'        => Wallet::FREECHARGE,
            'token'         => '101wallettoken',
            'gateway_token' => 'invalid-gateway-token',
            'customer_id'   => self::GLOBAL_CUSTOMER,
            'terminal_id'   => $this->sharedTerminal->getId(),
            'created_at'    => $now->timestamp,
            'expired_at'    => Carbon::now()->addYear()->timestamp,
        ];

        // Create Token and AppToken
        $token = $this->fixtures->create('token', $tokenAttributes);

        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        $payment = $this->doAuthAndGetPayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(isset($wallet['reference1']));
    }

    protected function setUpWalletToken()
    {
        $tokenAttributes = [
            'method'        => 'wallet',
            'wallet'        => Wallet::FREECHARGE,
            'token'         => '101wallettoken',
            'gateway_token' => '8c31d80b-83ed-4f52-8377-71301790ccaa',
            'customer_id'   => self::GLOBAL_CUSTOMER,
            'terminal_id'   => $this->sharedTerminal->getId(),
            'created_at'    => Carbon::now()->timestamp,
            'expired_at'    => Carbon::now()->addYear()->timestamp,
        ];

        // Create Token and AppToken
        return $this->fixtures->create('token', $tokenAttributes);
    }

    protected function setUpAppToken()
    {
        $appToken = $this->fixtures->create(
            'app_token',
            ['customer_id' => self::GLOBAL_CUSTOMER]);

        return $appToken;
    }

    protected function setUpAutoDebitFeature()
    {
        $appToken = $this->fixtures->create(
            'feature',
            [
                'name' => 'wallet_auto_debit',
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ]);

        return $appToken;
    }

    protected function getDefaultPaymentArray($wallet = null)
    {
        $payment = [
            'amount'   => '50000',
            'currency' => 'INR',
            'email'    => 'test@razorpay.com',
            'contact'  => '9566819331',
            'method'   => 'wallet',
            'wallet'   => Wallet::FREECHARGE,
        ];

        return $payment;
    }

    protected function mockSession(array $data = null)
    {
        if ($data !== null)
        {
            $this->session($data);
        }
    }

    protected function runPaymentCallbackFlowWalletFreecharge($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;
        $this->callbackUrl  = $url;

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }
        }

        return null;
    }
}
