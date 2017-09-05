<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Customer extends Base
{
    public function setUp()
    {
        $this->fixtures->create('customer:customers');
        $this->fixtures->create('customer:app_tokens');
        $this->fixtures->create('customer:tokens');
        $this->fixtures->create('customer:bank_accounts');
    }

    protected $customers = array(
        array(
            'id'            => '100000customer',
            'name'          => 'test',
            'email'         => 'test@razorpay.com',
            'contact'       => '1234567890',
            'merchant_id'   => '10000000000000'
        ),

        array(
            'id'            => '10000gcustomer',
            'name'          => 'test',
            'email'         => 'test@razorpay.com',
            'contact'       => '+919988776655',
            'merchant_id'   => '100000Razorpay'
        ),
    );

    protected $customerApps = array(
        array(
            'customer_id'   => '10000gcustomer',
            'id'            => '1000000custapp',
            'device_token'  => '1000custdevice',
            'merchant_id'   => '100000Razorpay',
        ),
    );

    protected $customerTokens = array(
        array(
            'id'            => '1000custwallet',
            'token'         => '100wallettoken',
            'customer_id'   => '100000customer',
            'method'        => 'wallet',
            'wallet'        => 'paytm',
            'bank'          => null,
            'card_id'       => null,
            'used_at'       => 10
        ),
        array(
            'id'            => '100000custbank',
            'token'         => '10000banktoken',
            'customer_id'   => '100000customer',
            'method'        => 'netbanking',
            'bank'          => 'HDFC',
            'wallet'        => null,
            'card_id'       => null,
            'used_at'       => 10
        ),
        array(
            'id'            => '100000custcard',
            'token'         => '10000cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'bank'          => null,
            'wallet'        => null,
            'recurring'     => false,
            'card_id'       => '100000000lcard',
            'used_at'       => 10
        ),
        array(
            'id'            => '100001custcard',
            'token'         => '10001cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'bank'          => null,
            'wallet'        => null,
            'card_id'       => '100000001lcard',
            'used_at'       => 10
        ),
        array(
            'id'            => '10000custgcard',
            'token'         => '1000gcardtoken',
            'customer_id'   => '10000gcustomer',
            'merchant_id'   => '100000Razorpay',
            'method'        => 'card',
            'card_id'       => '100000000gcard',
            'bank'          => null,
            'wallet'        => null,
            'used_at'       => 10
        ),
    );

    protected $bankAccounts = array(
        array(
            'id'            => '1000000lcustba',
            'merchant_id'   => '10000000000000',
            'entity_id'     => '100000customer',
            'type'          => 'customer'
        ),
    );

    public function createCustomers()
    {
        $customers = array();

        foreach ($this->customers as $customer)
        {
            $customers[] = $this->fixtures->create('customer', $customer);
        }

        return $customers;
    }

    public function createAppTokens()
    {
        $apps = array();

        foreach ($this->customerApps as $attributes)
        {
            $apps[] = $this->fixtures->create('app_token', $attributes);
        }

        return $apps;
    }

    public function createTokens()
    {
        $tokens = array();

        foreach ($this->customerTokens as $attributes)
        {
            $tokens[] = $this->fixtures->create('token', $attributes);
        }

        return $tokens;
    }

    public function createBankAccounts()
    {
        $bankAccounts = array();

        foreach ($this->bankAccounts as $attributes)
        {
            $bankAccounts[] = $this->fixtures->create('bank_account', $attributes);
        }

        return $bankAccounts;
    }

    public function createCustomerBalance($attributes = [])
    {
        $merchantId = '10000000000000';
        $customerValues = [
            'id'            => '200000customer',
            'name'          => 'Balancetest',
            'email'         => 'balancetest@razorpay.com',
            'contact'       => '1234567890',
            'merchant_id'   => $merchantId
        ];

        $customer = $this->fixtures->create('customer', $customerValues);

        $customerBalanceValues = [
            'customer_id'   => $customer->getId(),
            'merchant_id'   => $merchantId,
            'balance'       => 0,
            'daily_usage'   => 0,
            'weekly_usage'  => 0,
            'monthly_usage' => 0,
            'max_balance'   => 2000000,
        ];

        $customerBalanceValues = array_merge($customerBalanceValues, $attributes);

        return $this->fixtures->create('customer_balance', $customerBalanceValues);
    }
}
