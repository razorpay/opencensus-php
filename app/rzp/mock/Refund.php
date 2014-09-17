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
        $this->mock(static::$refund);

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(static::$refund);

        return $this;
    }
}
