<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class D2cBureauDetail extends Base
{
    protected $defaultAttributes = [

        'merchant_id'      => '10000000000000',
        'user_id'          => User::MERCHANT_USER_ID,
        'first_name'        => 'john',
        'last_name'         => 'doe',
        'contact_mobile'    => '9876543210',
        'email'             => 'tabitha.damore@mraz.biz',
        'address'           => 'Adress',
        'city'              => 'city',
        'state'             => 'PB',
        'pincode'           => '123455',
        'pan'               => 'ABCPE1234F',
    ];

    public function createCreatedOwner(array $attributes = array())
    {
        $defaultValues = [
            'status'      => 'created',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }
}
