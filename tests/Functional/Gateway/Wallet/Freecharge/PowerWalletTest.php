<?php
namespace RZP\Tests\Functional\Payment;
use Carbon\Carbon;
use Mockery;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Merchant\Account;
use RZP\Models\Customer\AppToken;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
class PowerWalletTest extends TestCase
{
    use PaymentTrait;
    const LOCAL_CUSTOMER = '100000customer';
    const GLOBAL_CUSTOMER = '10000gcustomer';
    public function setUp()
    {
        parent::setUp();
        // Use Freecharge Power Wallet for tests
        // TODO: Test PowerWallet Flow for other wallets
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_freecharge_terminal');
        $this->fixtures->merchant->enableWallet(Account::TEST_ACCOUNT, Wallet::FREECHARGE);
        $this->ba->publicAuth();
    }

    public function testPowerWalletPayment()
    {
        $this->setUpWalletToken();
        $appToken = $this->setUpAppToken();
        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];
        $this->mockSession($sessionData);
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndGetPayment($payment);
        $wallet = $this->getLastEntity('wallet', true);
        $this->assertTrue(empty($wallet['reference1']));
    }
    public function testUserNotAuthenticated()
    {
        // Flow should go through otpGenerate
        $this->setUpWalletToken();
        $attributes = $this->getDefaultPaymentArray();
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
        $attributes = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndGetPayment();
        $wallet = $this->getLastEntity('wallet', true);
        $this->assertTrue(isset($wallet['reference1']));
    }

//    public function testInSufficientWalletBalance()
//    {
//        $this->setUpWalletToken();
//        $appToken = $this->setUpAppToken();
//        $sessionData = [
//            'test_app_token' => $appToken->getPublicId(),
//        ];
//        $this->mockSession($sessionData);
//        // Mock will return Insufficient Balance
//        $this->setOtp(Otp::INSUFFICIENT_BALANCE);
//        $payment = $this->getDefaultPaymentArray();
//        $payment['amount'] = 100000;
//        $response = $this->doAuthPayment($payment);
//        $wallet = $this->getLastEntity('wallet', true);
//        $this->assertEquals($response['type'], 'topup');
//        $this->assertEquals($wallet['reference1'], null);
//    }

    public function testUserWalletTokenExpired()
    {
        $now = Carbon::now();
        $attr = [
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
        $token = $this->fixtures->create('token', $attr);
        $appToken = $this->setUpAppToken();
        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];
        $this->mockSession($sessionData);
        $attributes = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndGetPayment();
        $wallet = $this->getLastEntity('wallet', true);
        $this->assertTrue(isset($wallet['reference1']));
    }

    public function testInvalidGatewayToken()
    {
        $now = Carbon::now();
        $attr = [
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
        $token = $this->fixtures->create('token', $attr);
        $appToken = $this->setUpAppToken();
        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];
        $this->mockSession($sessionData);
        $attributes = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndGetPayment();
        $wallet = $this->getLastEntity('wallet', true);
        $this->assertTrue(isset($wallet['reference1']));
    }
    protected function setUpWalletToken()
    {
        $attr = [
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
        $token = $this->fixtures->create('token', $attr);
        return $token;
    }
    protected function setUpAppToken()
    {
        $appToken = $this->fixtures->create(
            'app_token',
            ['customer_id' => self::GLOBAL_CUSTOMER]);

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