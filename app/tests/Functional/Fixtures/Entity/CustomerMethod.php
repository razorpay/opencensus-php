<?php

namespace Tests\Functional\Fixtures\Entity;

class CustomerMethod extends Base
{
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
        )
    );

    public function createDefaultCustomerMethods()
    {
        $methods = [];

        foreach ($this->customerMethods as $attributes)
        {
            $methods[] = $this->fixtures->create('customer_method', $attributes);
        }

        return $methods;
    }
}