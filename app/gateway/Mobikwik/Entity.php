<?php

namespace Gateway\Mobikwik;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'orderid',
        'merchantname',
    );

    protected $fillable = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'orderid',
        'merchantname',
    );

    protected $table = 'mobikwik';

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