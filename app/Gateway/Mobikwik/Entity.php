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
}