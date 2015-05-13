<?php

namespace Gateway\Paytm;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'request_type',
        'cust_id',
        'channel_id',
        'payment_mode_only',
        'auth_mode',
        'bank_code',
        'payment_type_id',
        'industry_type_id',
        'website',
        'bank_code',
        'orderid',
        'txnamount',
        'currency',
        'txnid',
        'banktxnid',
        'status',
        'respcode',
        'respmsg',
        'txndate',
        'gatewayname',
        'bankname',
        'paymentmode',
    );

    protected $fillable = array(
        'request_type',
        'cust_id',
        'channel_id',
        'payment_mode_only',
        'auth_mode',
        'bank_code',
        'payment_type_id',
        'industry_type_id',
        'website',
        'bank_code',
        'orderid',
        'txnamount',
        'currency',
        'txnid',
        'banktxnid',
        'status',
        'respcode',
        'respmsg',
        'txndate',
        'gatewayname',
        'bankname',
        'paymentmode',
    );

    protected $table = 'paytm';

    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'paytm';

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }
}