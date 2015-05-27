<?php

namespace Gateway\Netbanking;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $table = 'netbanking';

    protected $entity = 'netbanking';

    protected $fields = array(
        'id',
        'payment_id',
        'bank',
        'amount',
        'client_code',
        'merchant_code',
        'bank_payment_id',
        'error_message',
        'reference1',
    );

    protected $fillable = array(
        'bank',
        'amount',
        'client_code',
        'merchant_code',
        'bank_payment_id',
        'error_message',
        'reference1',
    );

    public function setBank($bank)
    {
        $this->setAttribute('bank', $bank);
    }
}