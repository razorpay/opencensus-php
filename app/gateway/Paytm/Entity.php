<?php

namespace Gateway\Paytm;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'received',
        'request_type',
        'cust_id',
        'channel_id',
        'payment_mode_only',
        'auth_mode',
        'bank_code',
        'payment_type_id',
        'industry_type_id',
        'orderid',
        'txn_amount',
        'txnamount',
        'refundamount',
        'txnid',
        'txntype',
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
        'id',
        'payment_id',
        'refund_id',
        'action',
        'received',
        'request_type',
        'cust_id',
        'channel_id',
        'payment_mode_only',
        'auth_mode',
        'bank_code',
        'payment_type_id',
        'industry_type_id',
        'orderid',
        'txn_amount',
        'txnamount',
        'refundamount',
        'txnid',
        'txntype',
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