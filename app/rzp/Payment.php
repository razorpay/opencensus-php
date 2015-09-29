<?php

namespace RZP;

class Payment extends \Razorpay\Api\Payment
{
    public function verify()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/verify';

        return $this->request('GET', $relativeUrl);
    }

    public function verifyAll()
    {
        $relativeUrl = $this->getEntityUrl() . 'verify/all';

        return $this->request('GET', $relativeUrl);
    }

    public function authorizeFailed()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/authorize_failed';

        return $this->request('POST', $relativeUrl);
    }

    public function refundAuthorized()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/authorize_refund';

        return $this->request('POST', $relativeUrl);
    }
}
