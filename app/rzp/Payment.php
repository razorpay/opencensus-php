<?php

namespace RZP;
use Razorpay\Api;

class Payment extends Api\Payment
{   
    public function verify()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/verify';

        return $this->request('GET`', $relativeUrl, $attributes);
    }
}