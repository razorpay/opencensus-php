<?php

namespace RZP\Mock;

use RZP\Entity;

class Transaction extends Entity
{
    protected static $transaction;

    public function __construct()
    {
        static::$transaction = array(
            'id'                  => "txn-13946931b04cd00f45057372",
            'entity'              => "transaction",
            'amount'              => "499",
            'currency'            => "INR",
            'status'              => "authorized",
            'amount_refunded'     => "0",
            'refund_status'       => "none",
            'description'         => NULL,
            'email'               => "shk@gmail.com",
            'contact'             => "1234567890",
            'udf'                 => array(),
            'error_code'          => NULL,
            'error_description'   => NULL,
            'created_at'          => time()
        );
    }

    /**
     * @param $id Transaction id
     */
    public function fetch($id)
    {
        $this->fill(static::$transaction);

        return $this;
    }

    public function all($options = array())
    {
        $collection = array(
            'entity'    => 'collection',
            'count'     => 1,
            'data'      => array(static::$transaction)
        );

        $this->fill($collection);

        return $this;
    }

    /**
     * @param $id Transaction id
     */
    public function refund($attributes = array())
    {
        $refund = array(
            'id'                => "rfnd-139469414bbe64deb0d1c0c5",
            'entity'            => "refund",
            'amount'            => $attributes['amount'],
            'currency'          => "INR",
            'transaction_id'    => $this->id,
            'created_at'        => time()
        );

        $this->fill($refund);
        
        return $this;
    }

    /**
     * @param $id Transaction id
     */
    public function capture($attributes = array())
    {
        $this->fill(static::$transaction);

        $this->status = "captured";

        return $this;
    }

    public function refunds()
    {
        $entity = new Refund();

        $entity->transaction_id = $this->id;

        return $entity;
    }
}
