<?php

namespace App\RZP;

use Razorpay;

class Customer extends Razorpay\Api\Customer
{
    public function all($options = array())
    {
        return parent::all($options);
    }
}
