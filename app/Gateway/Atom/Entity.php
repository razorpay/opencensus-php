<?php

namespace RZP\Gateway\Atom;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'id',
        'gateway_payment_id',
        'token',
        'success',
        'callback_data',
        'bank_code',
        'bank_name',
        'bank_payment_id',
        'gateway_result_description',
        'method',
        'created_at',
        'updated_at');

    protected $fillable = array(
        'id',
        'bank_code',
        'gateway_payment_id',
        'token',
        'bank_code',
        'bank_name',
        'bank_payment_id',
        'gateway_result_description',
        'method');

    protected static $sign = 'pay';

    protected $entity = 'atom';

    public function setSuccess($success)
    {
        $this->setAttribute('success', $success);
    }

    public function setBankName($name)
    {
        $this->setAttribute('bank_name', $name);
    }

    public function setBankPaymentId($bankPaymentId)
    {
        $this->setAttribute('bank_payment_id', $bankPaymentId);
    }

    public function setCallbackData($data)
    {
        $this->setAttribute('callback_data', $data);
    }

    public function setCallbackDataAttribute($data)
    {
        $this->attributes['callback_data'] = json_encode($data);
    }

    public function getCallbackDataAttribute()
    {
        return json_decode($this->attributes['callback_data'], true);
    }
}