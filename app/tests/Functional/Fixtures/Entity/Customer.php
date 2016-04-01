<?php

namespace Tests\Functional\Fixtures\Entity;

class Customer extends Base
{
    public function setUp()
    {
        $this->fixtures->create('customer:customers');
        $this->fixtures->create('customer:customer_apps');
        $this->fixtures->create('customer:customer_methods');
    }

    protected $customers = array(
        array(
            'id'            => '100000customer',
            'name'          => 'test',
            'contact'       => '1234567890',
            'merchant_id'   => '10000000000000'
        ),

        array(
            'id'            => '10000gcustomer',
            'name'          => 'test',
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

    protected $customerMethods = array(
        array(
            'id'            => '1000custwallet',
            'customer_id'   => '100000customer',
            'method'        => 'wallet',
            'wallet'        => 'paytm',
        ),
        array(
            'id'            => '100000custbank',
            'customer_id'   => '100000customer',
            'method'        => 'netbanking',
            'bank'          => 'HDFC',
        ),
        array(
            'id'            => '100000custcard',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'card_id'       => '1000000000card',
        ),
        array(
            'id'            => '10000custgcard',
            'customer_id'   => '10000gcustomer',
            'method'        => 'card',
            'card_id'       => '1000000000card',
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
        $apps = [];

        foreach ($this->customerApps as $attributes)
        {
            $apps[] = $this->fixtures->create('customer_app', $attributes);
        }

        return $apps;
    }

    public function createCustomerMethods()
    {
        $methods = [];

        foreach ($this->customerMethods as $attributes)
        {
            $methods[] = $this->fixtures->create('customer_method', $attributes);
        }

        return $methods;
    }
}