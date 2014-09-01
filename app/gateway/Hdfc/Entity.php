<?php

namespace Gateway\Hdfc;

class Entity extends \Models\Base\Entity
{
    protected $fields = array(
        'id',
        'gateway_transaction_id',
        'trackid',
        'action',
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

    protected $primaryKey = 'gateway_transaction_id';

    protected $guarded = array();

    public function transaction()
    {
        return $this->belongsTo('Transaction', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }
}