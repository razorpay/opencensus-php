<?php

namespace App\RZP;

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

    /**
     * Overridden here because we need to use App\RZP\Payment instead of
     * Razorpay\Api\Payment
     * Because Payment class was missing this method because of being
     * derived from Razorpay\Api\Payment
     */
    protected static function getEntityClass($name)
    {
        return 'App\RZP\Payment';
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function card()
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/card';

        return $this->request('GET', $relativeUrl);
    }
}
