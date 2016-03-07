<?php

namespace Tests\Functional\Fixtures\Entity;

class Customer extends Base
{
    protected $customer = array(
        'id'            => '100000customer',
        'name'          => 'test',
        'contact'       => '1234567890',
        'email'         => 'test@razorpay.com',
        'merchant_id'   => '10000000000000'
    );

    public function createDefaultCustomers()
    {
        $this->fixtures->create('customer', $this->customer);
    }
}