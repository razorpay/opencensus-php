<?php

namespace Gateway\Wallet\Base;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $table = 'wallet';

    protected $entity = 'wallet';

    protected $fields = array(
        'id',
        'payment_id',
        'wallet',
        'received',
        'amount',
        'email',
        'contact',
        'gateway_merchant_id',
        'gateway_payment_id',
        'gateway_payment_id_2',
        'gateway_refund_id',
        'response_code',
        'response_description',
        'status_code',
        'merchant_code',
        'error_message',
        'date',
        'refund_id',
        'reference1',
        'reference2',
    );

    protected $fillable = array(
        'payment_id',
        'wallet',
        'received',
        'amount',
        'email',
        'contact',
        'gateway_merchant_id',
        'gateway_payment_id',
        'gateway_payment_id_2',
        'gateway_refund_id',
        'response_code',
        'response_description',
        'status_code',
        'merchant_code',
        'error_message',
        'date',
        'refund_id',
        'reference1',
        'reference2',
    );

    public function setWallet($wallet)
    {
        $this->setAttribute('wallet', $wallet);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes['amount'];
    }
}