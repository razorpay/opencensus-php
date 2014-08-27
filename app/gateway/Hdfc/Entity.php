<?php

namespace Gateway\Hdfc;

class Entity extends \Models\Base\Entity
{
    protected $fields = array(
        'id',
        'trackid',
        'transactionid',
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
        'error_service',
        'created_at',
        'updated_at');

    protected $table = 'hdfc';

    protected $primaryKey = 'transactionid';

    public $incrementing = false;

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