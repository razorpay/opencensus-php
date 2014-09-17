<?php

namespace RZP\Mock;

use RZP\Entity;

class Refund extends Entity
{
    protected static $refund;

    public function __construct()
    {
        static::$refund = array(
            'id'                => "rfnd-139469414bbe64deb0d1c0c5",
            'entity'            => "refund",
            'amount'            => "100",
            'currency'          => "INR",
            'transaction_id'    => "txn-13946931b04cd00f45057372",
            'created_at'        => time()
        );
    }

    /**
     * @param $id Merchant id
     */
    public function fetch($id)
    {
        $this->fill(static::$refund);

        return $this;
    }

    public function all($options = array())
    {
        $collection = array(
            'entity'    => 'collection',
            'count'     => 1,
            'data'      => array(static::$refund)
        );

        $this->fill($collection);

        return $this;
    }
}
