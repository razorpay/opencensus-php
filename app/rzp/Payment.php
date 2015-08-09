<?php

namespace RZP;

class Payment extends \Razorpay\Api\Payment
{
    public function verify()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/verify';

        return $this->request('GET`', $relativeUrl);
    }
}