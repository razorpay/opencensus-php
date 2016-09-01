<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Card extends Base
{
    protected $items = array(
        array(
            'id'                =>  '100000000lcard',
            'merchant_id'       =>  '10000000000000',
            'name'              =>  'test',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '411111',
            'last4'             =>  '1111'
        ),
        array(
            'id'                =>  '100000000gcard',
            'merchant_id'       =>  '100000Razorpay',
            'name'              =>  'test',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '411111',
            'last4'             =>  '1111'
        ),
    );

    public function createDefaultCards()
    {
        $items = $this->items;

        $cards = [];
        foreach ($items as $attributes)
        {
            $cards[] = $this->fixtures->create('card', $attributes);
        }

        return $cards;
    }
}