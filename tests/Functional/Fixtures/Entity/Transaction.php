<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Transaction extends Base
{
    public function createEmandateRegistration(array $attributes = array())
    {
        $defaultValues = [
            'type'        => 'payment',
            'merchant_id' => '10000000000000',
            'amount'      => 0,
            'fee'         => 1180,
            'tax'         => 180,
            'debit'       => 1180,
            'fee_bearer'  => 'platform'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('transaction', $attributes);
    }
}
