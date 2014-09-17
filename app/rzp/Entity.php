<?php

namespace RZP;

use Razorpay;

class Entity extends Razorpay\Api\Entity
{
    protected function mock($data, $attributes = array())
    {
        $this->fill($data);

        foreach($attributes as $key => $value)
        {
            $this->$key = $value;
        }
    }

    protected function mockCollection($data)
    {
        $collection = array(
            'entity'    => 'collection',
            'count'     => 1,
            'data'      => array($data)
        );

        $this->fill($collection);
    }
}