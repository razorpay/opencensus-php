<?php

namespace Tests\Functional\Fixtures\Entity;

class Customer extends Base
{
    public function setUp()
    {
        $this->fixtures->create('customer:customers');
        //$this->fixtures->create('customer:customer_apps');
        $this->fixtures->create('customer:tokens');
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
            'contact'       => '1234567890',
            'merchant_id'   => '100000Razorpay'
        ),
    );

    protected $customerApps = array(
        array(
            'id'            => '1000custappuid',
            'customer_id'   => '10000gcustomer',
            'device_id'     => 'test',
            'app_id'        => '1000000custapp',
        ),
    );

    protected $customerTokens = array(
        array(
            'id'            => '1000custwallet',
            'token'         => '100wallettoken',
            'customer_id'   => '100000customer',
            'method'        => 'wallet',
            'wallet'        => 'paytm',
        ),
        array(
            'id'            => '100000custbank',
            'token'         => '10000banktoken',
            'customer_id'   => '100000customer',
            'method'        => 'netbanking',
            'bank'          => 'HDFC',
        ),
        array(
            'id'            => '100000custcard',
            'token'         => '10000cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'card_id'       => '100000000lcard',
        ),
        array(
            'id'            => '10000custgcard',
            'token'         => '1000gcardtoken',
            'customer_id'   => '10000gcustomer',
            'method'        => 'card',
            'card_id'       => '100000000gcard',
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

    public function createCustomerApps()
    {
        $apps = array();

        foreach ($this->customerApps as $attributes)
        {
            $apps[] = $this->fixtures->create('customer_app', $attributes);
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
}