<?php

namespace RZP;

class Order extends \Razorpay\Api\Entity
{
    /**
     * @param $id Order id
     */
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

}
