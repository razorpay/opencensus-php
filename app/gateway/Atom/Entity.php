<?php

namespace Gateway\Atom;

class Entity extends \Models\Base\Entity
{
    protected $fields = array(
        'id',
        'gateway_payment_id',
        'token',
        'success',
        'callback_data',
        'bank_name',
        'bank_transaction_id',
        'created_at',
        'updated_at');

    protected $fillable = array(
        'id',
        'gateway_payment_id',
        'token');

    protected $table = 'atom';

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('Payment', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }

    public function setSuccess($success)
    {
        $this->setAttribute('success', $success);
    }

    public function setBankName($name)
    {
        $this->setAttribute('bank_name', $name);
    }

    public function setBankTransactionId($bankTransactionId)
    {
        $this->setAttribute('bank_transaction_id', $bankTransactionId);
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