<?php

namespace Gateway\Hdfc;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'gateway_transaction_id',
        'payment_id',
        'refund_id',
        'action',
        'amount',
        'enroll_result',
        'status',
        'auth_result',
        'eci',
        'auth',
        'ref',
        'avr',
        'postdate',
        'error_code',
        'error_text',
        'created_at',
        'updated_at');

    protected $table = 'hdfc';

    protected $primaryKey = 'id';

    protected $entity = 'hdfc';

    public $incrementing = true;

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('Payment', 'payment_id', 'id');
    }

    public function getPaymentId()
    {
        return $this->getAttribute('payment_id');
    }

    public function getRefundId()
    {
        return $this->getAttribute('refund_id');
    }
}