<?php

namespace RZP\Mock;

use RZP\Entity;

class Key extends Entity
{   
    protected static $key;

    public function __construct()
    {
        static::$key = array(
            'id'            => "rzp_test_1394bbab963387ab84de5ef8",
            'entity'        => "key",
            'created_at'    => time(),
            'expired_at'    => NULL,
            'secret'        => "thisissecret"
        );
    }

    public function create($params = null)
    {
        $this->mock(static::$key);

        return $this;
    }

    public function roll($params = null)
    {
        $roll = array(
            'old_id'      => $this->id,
            'merchant_id' => $this->merchant_id,
            'new'         => static::$key
        );

        $this->mock($roll);

        return $this;
    }

    public function fetch($id)
    {
        $this->id = $id;

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(static::$key);

        return $this;
    }
}