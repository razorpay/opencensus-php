<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Token extends Base
{
    protected $defaultAttributes = [
        'customer_id'      => '100000customer',
        'merchant_id'      => '10000000000000',
        'method'           => 'emandate',
        'max_amount'       => 9999900,
        'account_number'   => '0123456789',
        'beneficiary_name' => 'Test Account',
        'auth_type'        => 'netbanking',
    ];

    public function createEmandateConfirmed(array $attributes = array())
    {
        $defaultValues = [
            'merchant_id'      => '10000000000000',
            'customer_id'      => '100000customer',
            'method'           => 'emandate',
            'wallet'           => null,
            'max_amount'       => '9999900',
            'account_number'   => 1234567890,
            'beneficiary_name' => 'Test account',
            'ifsc'             => 'UTIB0002766',
            'gateway_token'    => '8888888888',
            'auth_type'        => 'netbanking',
            'recurring'        => true,
            'recurring_status' => 'confirmed',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createEmandateRegistrationInitiated(array $attributes = [])
    {
        $defaults = array_merge(
            $this->defaultAttributes,
            [
                'recurring'        => false,
                'recurring_status' => 'initiated',
            ]
        );

        $attributes = array_merge($defaults, $attributes);

        return parent::create($attributes);
    }

    public function createEmandateRegistrationConfirmed(array $attributes = [])
    {
        $defaults = array_merge(
            $this->defaultAttributes,
            [
                'recurring'        => true,
                'recurring_status' => 'confirmed',
            ]
        );

        $attributes = array_merge($defaults, $attributes);

        return parent::create($attributes);
    }
}
