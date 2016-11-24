<?php

namespace RZP\Gateway\Hdfc;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
        'action',
        'received',
        'gateway_transaction_id',
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

    protected $fillable = array(
        'payment_id',
        'refund_id',
        'gateway_transaction_id',
        'action',
        'received',
        'amount',
        'enroll_result',
        'status',
        'result',
        'eci',
        'auth',
        'ref',
        'avr',
        'postdate',
        'error_code',
        'error_text',
    );

    protected $entity = 'hdfc';

    public $incrementing = true;

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', 'payment_id', 'id');
    }

    public function getAction()
    {
        return $this->getAttribute('action');
    }

    public function getStatus()
    {
        return $this->getAttribute('status');
    }

    public function getErrorCode()
    {
        return $this->getAttribute('error_code');
    }

    public function getResult()
    {
        return $this->getAttribute('result');
    }

    public function getEnrollResult()
    {
        return $this->getAttribute('enroll_result');
    }

    public function getReceivedAttribute()
    {
        $received = $this->attributes['received'];

        if ($received !== null)
        {
            $received = (bool) $received;
        }

        return $received;
    }

    public function setReceived($value)
    {
        $this->setAttribute('received', $value);
    }

    public function setStatus($status)
    {
        $this->setAttribute('status', $status);
    }

    public function setGatewayTransactionId($txnId)
    {
        $this->setAttribute('gateway_transaction_id', $txnId);
    }

    public function getTransactionId()
    {
        return $this->getAttribute('gateway_transaction_id');
    }

    public function getAuthCode()
    {
        return $this->getAttribute('auth');
    }

    public function getActionAttribute()
    {
        return (int) $this->attributes['action'];
    }
}
