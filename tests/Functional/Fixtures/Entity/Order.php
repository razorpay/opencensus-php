<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Order extends Base
{
    public function createTpvOrder(Array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'account_number'            => '0001231321321',
            'bank'                      => 'ICIC',
            'method'                    => 'netbanking',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createOrderWithOfferApplied(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }
}
