<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Address extends Base
{
    // TODO: Remove this class.
    
    public function setUp()
    {
        $this->fixtures->create('customer:customers', ['id'=>'cc']);
    }

    public function createaaa(array $attributes = array())
    {
        $defaultAttributes = [
            'line_one'  => 'some line one',
            'line two'  => 'some line two',
            'city'      => 'Bangalore',
            'state'     => 'Karnataka',
            'pincode'   => '560078',
            'country'   => 'India',
            'address_type'  => 'shipping_address'
        ];

        $attributes = array_merge($attributes, $defaultAttributes);

        return parent::create($attributes);
    }
}