<?php

namespace Gateway\Base;

class Entity extends \Models\Base\PublicEntity
{
    const PAYMENT_ID    = 'payment_id';
    const REFUND_ID     = 'refund_id';
    const ACTION        = 'action';
    const RECEIVED      = 'received';

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

    public function getPaymentId()
    {
        return $this->getAttribute('payment_id');
    }

    public function getRefundId()
    {
        return $this->getAttribute('refund_id');
    }
}