<?php

namespace RZP\Gateway\Base;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID    = 'payment_id';
    const REFUND_ID     = 'refund_id';
    const ACTION        = 'action';
    const RECEIVED      = 'received';

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }

    public function getPaymentId()
    {
        return $this->getAttribute('payment_id');
    }

    public function getPublicPaymentId()
    {
        return 'pay_' . $this->getPaymentId();
    }

    public function getRefundId()
    {
        return $this->getAttribute('refund_id');
    }

    public function setRefundId($refundId)
    {
        $this->setAttribute('refund_id', $refundId);
    }

    public function setPaymentId($paymentId)
    {
        $this->setAttribute('payment_id', $paymentId);
    }

    public function setAction($action)
    {
        $this->setAttribute('action', $action);
    }

    protected function getReceivedAttribute()
    {
        return (bool) $this->attributes['received'];
    }
}
