<?php

namespace Functional\Customer;

use Carbon\Carbon;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CustomerTokensInternalTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTokensInternalTestData.php';

        parent::setUp();
    }

    public function testFetchCustomerTokensInternalForGlobalCustomer()
    {
        $this->mockSession();

        $globalCustomerId = '10000gcustomer';

        $cardId1 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'iin' => '526731',
            'vault' => 'mastercard',
            'network' => 'MasterCard',
            'last4' => '5449',
            'issuer' => 'KKBK',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId1 = 'KuClzN7vGGpga0';
        $this->createTokenFixture($cardId1, Account::TEST_ACCOUNT, [
            'id' => $tokenId1,
            'token' => 'KuClzN8Q5ttGR8',
            'customer_id' => $globalCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $cardId2 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'issuer' => 'HDFC',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId2 = 'KuClzOupNoxchg';
        $this->createTokenFixture($cardId2, Account::TEST_ACCOUNT, [
            'id' => $tokenId2,
            'token' => 'KuClzOvHeBP36p',
            'customer_id' => $globalCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testFetchCustomerTokensInternalForLocalCustomer()
    {
        $localCustomerId = '100001customer';

        $this->fixtures->create('customer', [
            'id' => $localCustomerId,
            'merchant_id' => Account::TEST_ACCOUNT,
        ]);

        $cardId1 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'iin' => '526731',
            'vault' => 'mastercard',
            'network' => 'MasterCard',
            'last4' => '5449',
            'issuer' => 'KKBK',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId1 = 'KuClzN7vGGpga0';
        $this->createTokenFixture($cardId1, Account::TEST_ACCOUNT, [
            'id' => $tokenId1,
            'token' => 'KuClzN8Q5ttGR8',
            'customer_id' => $localCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162,
        ]);

        $cardId2 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'issuer' => 'HDFC',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId2 = 'KuClzOupNoxchg';
        $this->createTokenFixture($cardId2, Account::TEST_ACCOUNT, [
            'id' => $tokenId2,
            'token' => 'KuClzOvHeBP36p',
            'customer_id' => $localCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    protected function mockSession($appToken = 'capp_1000000custapp'): void
    {
        $data = ['test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function createTokenFixture($cardId, $merchantId = '100000Razorpay', array $attributes = [])
    {
        $token = $this->fixtures->create('token', array_merge([
            'method'      => 'card',
            'card_id'     => $cardId,
            'customer_id' => '10000gcustomer',
            'merchant_id' => $merchantId,
            'used_at'     => Carbon::now()->getTimestamp(),
            'used_count'  => 1,
            'status'      => 'active',
        ], $attributes
        ));

        return $token->getId();
    }

    protected function createCardFixture(array $attributes = [])
    {
        $card = $this->fixtures->create('card', array_merge([
            'country'       => 'IN',
            "last4"         => "1234",
            "network"       => "Visa",
            "type"          => "credit",
            "issuer"        => "sbi",
            "expiry_month"  => 12,
            "expiry_year"   => 2024,
            'vault'         => 'visa',
        ],
            $attributes
        ));

        return $card->getId();
    }
}
