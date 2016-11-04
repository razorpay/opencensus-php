<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
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

    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'mobikwik';

    public $incrementing = true;

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