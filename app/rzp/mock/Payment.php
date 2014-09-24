<?php

namespace RZP\Mock;

class Transaction extends MockEntity
{
    /**
     * @param $id Transaction id
     */
    public function fetch($id)
    {
        $this->mock(self::$mockData['transaction'], array('id' => $id));

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['transaction']);

        return $this;
    }

    /**
     * @param $id Transaction id
     */
    public function refund($attributes = array())
    {
        $this->mock(self::$mockData['refund'], array('amount' => $attributes['amount']));
        
        return $this;
    }

    /**
     * @param $id Transaction id
     */
    public function capture($attributes = array())
    {
        $this->mock(self::$mockData['transaction'], array('status' => "captured"));

        return $this;
    }

    public function refunds()
    {
        $entity = new Refund();

        $entity->transaction_id = $this->id;

        return $entity;
    }
}
