<?php

namespace App\RZP\Mock;

class Payment extends MockEntity
{
    /**
     * @param $id Payment id
     */
    public function fetch($id)
    {
        $this->mock(self::$mockData['payment'], array('id' => $id));

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['payment']);

        return $this;
    }

    /**
     * @param $id Payment id
     */
    public function refund($attributes = array())
    {
        $this->mock(self::$mockData['refund'], array('amount' => $attributes['amount']));
        
        return $this;
    }

    /**
     * @param $id Payment id
     */
    public function capture($attributes = array())
    {
        $this->mock(self::$mockData['payment'], array('status' => "captured"));

        return $this;
    }

    public function refunds()
    {
        $entity = new Refund();

        $entity->payment_id = $this->id;

        return $entity;
    }
}
