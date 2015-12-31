<?php

namespace Gateway\Netbanking\Base;

use Gateway\Base;

class Entity extends Base\Entity
{
    const CAPS_PAYMENT_ID = 'caps_payment_id';

    protected $table = 'netbanking';

    protected $entity = 'netbanking';

    protected $fields = array(
        'id',
        'payment_id',
        'bank',
        'received',
        'amount',
        'client_code',
        'merchant_code',
        'bank_payment_id',
        'error_message',
        'date',
        'refund_id',
        'reference1',
        'int_payment_id',
    );

    protected $fillable = array(
        'bank',
        'amount',
        'received',
        'client_code',
        'merchant_code',
        'bank_payment_id',
        'error_message',
        'date',
        'status',
        'refund_id',
        'reference1',
        'int_payment_id',
    );

    public function setBank($bank)
    {
        $this->setAttribute('bank', $bank);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes['amount'];
    }

    public function setPaymentId($paymentId)
    {
        parent::setPaymentId($paymentId);

        $this->attributes['caps_payment_id'] = strtoupper($paymentId);
    }
}