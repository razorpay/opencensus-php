<?php

namespace RZP\Mock;

use RZP\Entity;

class Pricing extends Entity
{   
    protected static $pricing;

    protected static $rule;

    public function __construct()
    {
        static::$rule = array(
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
        );

        static::$pricing = array(
            'id'    => "1394832550f5e5d96eea81fe",
            'name'  => "mockPlan",
            'entity'=> "pricing_plan",
            'count' => 1,
            'rules' => array(static::$rule)
        );
    }

    public function create($params = null)
    {
        $this->mock(static::$pricing);

        return $this;
    }

    public function fetch($id)
    {
        $this->mock(static::$pricing, array('id' => $id));

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(static::$pricing);

        return $this;
    }

    public function merchants()
    {
        return $this->all();
    }

    public function gateways()
    {
        return $this->all();
    }

    public function fetchRule($id)
    {
        $this->mock(static::$rule, array('id' => $id));

        return $this;
    }

    public function createRule($params)
    {
        $this->mock(static::$rule);

        return $this;
    }
}