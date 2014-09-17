<?php

namespace RZP\Mock;

use RZP\Entity;

class Merchant extends Entity
{
    protected static $merchant;

    public function __construct()
    {
        static::$merchant = array(
            'id'    =>  "363e4efa820b0c06208ccd99",
            'name'  =>  "Harshil",
            'email' =>  "test@razorpay.com",
            'activated' => 0,
            'live'      => 0,
            'pricing_plan_id' => null,
            'created_at'      => time(),
            'updated_at'      => time(),
            'entity'          => "merchant"
        );
    }
    public function create($params = null)
    {
        $this->fill(static::$merchant);

        return $this;
    }

    public function fetch($id)
    {   

        $this->fill(static::$merchant);

        $this->id = $id;

        return $this;
    }

    public function all($options = array())
    {
        $collection = array(
            'count'     =>  1,
            'entity'    => 'collection',
            'data'      =>array(static::$merchant)
        );

        $this->fill($collection);

        return $this;
    }

    public function keys()
    {
        $entity = new Key;

        $entity->merchant_id = $this->id;

        return $entity;
    }

    public function activate()
    {
        $this->fill(static::$merchant);

        $this->activated = 1;

        return $this;
    }

    //Enables live transactions for merchant
    public function enable()
    {
        $this->fill(static::$merchant);

        $this->activated = 1;

        $this->live = 1;

        return $this;
    }

    //disable live transactions for merchant
    public function disable()
    {
        $this->fill(static::$merchant);

        $this->activated = 1;

        return $this;
    }

    public function fetchPricing()
    {
        $pricing = array();

        $this->fill($pricing);

        return $this;
    }

    public function setPricing($params)
    {
        $pricing = array(
            'id'    => "1394832550f5e5d96eea81fe",
            'name'  => "mockPlan",
            'entity'=> "pricing_plan",
            'count' => 1,
            'rules' => array(
                array(
                    'id'                => "139486ac075f729bb334aa57",
                    'plan_id'           => "1394832550f5e5d96eea81fe",
                    'plan_name'         => "testRule",
                    'gateway'           => NULL,
                    'payment_mode'      => "card",
                    'payment_mode_type' => "debit",
                    'payment_network'   => NULL,
                    'payment_issuer'    => NULL,
                    'percent_rate'      => "223",
                    'fixed_rate'        => "223",
                    'created_at'        => time(),
                    'updated_at'        => time(),
                    'expired_at'        => NULL
                )
            )
        );

        $this->fill($pricing);

        return $this;
    }

    public function fetchTerminal()
    {   
        $terminal = array();

        $this->fill($terminal);

        return $this;
    }

    public function setTerminal($params)
    {
        $terminal = array(
            'id'                    => "14aa47c4a93d6e9b9c7ef51a",
            'merchant_id'           => "363e4efa820b0c06208ccd99",
            'gateway'               => $params['gateway'],
            'gateway_merchant_id'   => $params['gateway_merchant_id'],
            'gateway_terminal_id'   => $params['gateway_terminal_id'],
            'created_at'            => time(),
            'updated_at'            => time()
        );

        $this->fill($terminal);

        return $this;
    }
}
