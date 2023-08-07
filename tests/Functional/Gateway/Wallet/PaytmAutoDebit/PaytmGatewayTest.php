<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;

class PaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'paytm';

    protected $payment;

    protected $merchantId = '10000000000000';

    const GLOBAL_CUSTOMER = '10000gcustomer';
    /**
     * @var array|mixed
     */
    private mixed $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:paytm_auto_debit_terminal', ['currency' => 'INR']);

        $this->gateway =  Wallet::PAYTM;
        $this->wallet = Wallet::PAYTM;

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableWallet($this->merchantId, self::WALLET);

        $this->fixtures->merchant->addFeatures(['raas', 'wallet_paytm_auto_debit']);

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->payment['contact']= '918448720400';
        $this->payment['force_terminal_id'] = $this->terminal->getId();
    }

    protected function mockSession(array $data = null)
    {
        if ($data !== null)
        {
            $this->session($data);
        }
    }

    protected function setUpAppToken()
    {
        return $this->fixtures->create(
            'app_token',
            ['customer_id' => self::GLOBAL_CUSTOMER]);
    }

    public function testPaytmLinkAndPayPayment()
    {
        $token = $this->setUpWalletToken();

        $appToken = $this->setUpAppToken();

        $sessionData = [
            'test_app_token' => $appToken->getPublicId(),
        ];

        $this->mockSession($sessionData);

        $this->doAuthAndCapturePayment();

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTrue(empty($wallet['reference1']));
    }

    protected function setUpWalletToken()
    {
        $tokenAttributes = [
            'method'        => 'wallet',
            'wallet'        => \RZP\Models\Payment\Processor\Wallet::PAYTM,
            'token'         => '101wallettoken',
            'gateway_token' => '8c31d80b-83ed-4f52-8377-71301790ccaa',
            'gateway_token2' => '8c31d80b-83ed-4f52-8377-71301790bbdd',
            'customer_id'   => self::GLOBAL_CUSTOMER,
            'terminal_id'   => $this->terminal->getId(),
            'created_at'    => Carbon::now()->timestamp,
            'expired_at'    => Carbon::now()->addYear()->timestamp,
        ];

        // Create Token and AppToken
        return $this->fixtures->create('token', $tokenAttributes);
    }
}
