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
            'last4'             =>  '1111',
            'issuer'            =>  'HDFC',
        ),
        array(
            'id'                =>  '100000000gcard',
            'merchant_id'       =>  '100000Razorpay',
            'name'              =>  'test',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '411111',
            'last4'             =>  '1111',
        ),
        array(
            'id'                =>  '100000001lcard',
            'merchant_id'       =>  '10000000000000',
            'name'              =>  'test',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '411111',
            'last4'             =>  '1111',
        ),
        array(
            'id'                =>  '10000000rucard',
            'merchant_id'       =>  '10000000000000',
            'name'              =>  'test',
            'network'           =>  'RuPay',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '607384',
            'last4'             =>  '1111',
            'vault_token'       => 'NjA3Mzg0OTcwMDAwNDk0Nw==',
            'vault'             => 'rzpvault',
        ),
        array(
            'id'                =>  '10000000ICcard',
            'merchant_id'       =>  '10000000000000',
            'name'              =>  'test',
            'network'           =>  'Visa',
            'expiry_month'      =>  '12',
            'expiry_year'       =>  '2100',
            'iin'               =>  '462846',
            'last4'             =>  '1111',
            'issuer'            =>  'ICIC',
        )
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
