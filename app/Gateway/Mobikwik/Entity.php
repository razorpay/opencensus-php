<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'orderid',
        'txid',
        'merchantname',
        'email',
        'amount',
        'cell',
        'showmobile',
        'statuscode',
        'statusmessage',
        'refid',
        'received',
        'ispartial'
    );

    protected $fillable = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'orderid',
        'txid',
        'merchantname',
        'email',
        'amount',
        'cell',
        'showmobile',
        'statuscode',
        'statusmessage',
        'refid',
        'received',
        'ispartial'
    );

    protected $entity = 'mobikwik';

// ----------------------- Setters --------------------------------------------

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }

    public function setAction($action)
    {
        $this->setAttribute('action', $action);
    }

    public function setMethod($method)
    {
        $this->setAttribute('method', $method);
    }

// ----------------------- Setters End ----------------------------------------

// ----------------------- Getters --------------------------------------------

    public function getRefundId()
    {
        return $this->getAttribute('refund_id');
    }

    public function getPaymentId()
    {
        return $this->getAttribute('payment_id');
    }

    public function getAmount()
    {
        return $this->getAttribute('amount');
    }

    public function getStatusCode()
    {
        return $this->getAttribute('statuscode');
    }

// ----------------------- Getters End -----------------------------------------

// ----------------------- Accessors --------------------------------------------

    public function getAmountAttribute(float $amount)
    {
        return $amount * 100;
    }

// ----------------------- Accessors End ----------------------------------------
}
