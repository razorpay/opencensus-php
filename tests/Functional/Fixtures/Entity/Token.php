<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Token extends Base
{
    public function createEmandateRegistration(array $attributes = [])
    {
        $defaults = [
            'customer_id'      => '100000customer',
            'merchant_id'      => '10000000000000',
            'method'           => 'emandate',
            'max_amount'       => 9999900,
            'account_number'   => '0123456789',
            'beneficiary_name' => 'Test Account',
            'auth_type'        => 'netbanking',
            'recurring'        => false,
            'recurring_status' => 'initiated',
        ];

        $attributes = array_merge($defaults, $attributes);

        return parent::create($attributes);
    }
}
