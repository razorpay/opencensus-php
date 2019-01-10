<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Netbanking extends Base
{
    public function createEmandateDebit(array $attributes = [])
    {
        $defaultAttributes = [
            'merchant_code'   => '10000000000000',
            'status'          => 'authorize',
            'received'        => false,
            'caps_payment_id' => strtoupper($attributes['payment_id']),
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->create('netbanking', $attributes);
    }
}
