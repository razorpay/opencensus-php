<?php

namespace Gateway\Base;

class Entity extends \Models\Base\PublicEntity
{
    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }

    public function setAction($action)
    {
        $this->setAttribute('action', $action);
    }

    public function getReceivedAttribute()
    {
        return (bool) $this->attributes['received'];
    }
}