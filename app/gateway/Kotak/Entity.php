<?php

namespace Gateway\Kotak;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'payment_id',
        'TxnType',
        'TxnRefNo',
        'OrderInfo',
        'Amount',
        'Currency',
        'MCC',
        'AuthCode',
        'CardType',
        'ResponseCode',
        'RetRefNo',
        'MerchantName',
        'MerchantCity',
        'MerchantState',
        'MerchPostalCode',
        'MerchPhone',
    );

    protected $fillable = array(
        'payment_id',
        'TxnType',
        'TxnRefNo',
        'OrderInfo',
        'Amount',
        'Currency',
        'MCC',
        'AuthCode',
        'CardType',
        'ResponseCode',
        'RetRefNo',
        'MerchantName',
        'MerchantCity',
        'MerchantState',
        'MerchPostalCode',
        'MerchPhone',
    );

    protected $table = 'kotak';

    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'kotak';

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }
}