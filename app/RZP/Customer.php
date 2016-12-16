<?php

namespace App\RZP;

use Razorpay;

class Customer extends Razorpay\Api\Customer
{
    public function all($options = [])
    {
        return parent::all($options);
    }

    public function delete($id)
    {
      return parent::request('DELETE', $this->getEntityUrl() . $id);
    }
}
