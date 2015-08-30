<?php

namespace Gateway\Netbanking\Base;

use Gateway\Base;

class Entity extends Base\Entity
{
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
        'refund_id',
        'reference1',
    );

    public function setBank($bank)
    {
        $this->setAttribute('bank', $bank);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes['amount'];
    }
}