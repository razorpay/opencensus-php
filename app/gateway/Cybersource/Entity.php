<?php

namespace Gateway\Cybersource;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'payment_id',
        'received',
        'amount',
        'status',
        'eci',
        'auth',
        'ref',
        'capture_ref',
        'error_code',
        'created_at',
        'updated_at',
    );

    protected $fillable = array(
        'payment_id',
        'amount',
        'status',
        'eci_raw',
        'cavv',
        'ref',
        'capture_ref',
        'error_code',
        'commerce_indicator',
        'xid',
        'pares_status',
        'auth_data',
        'collection_indicator',
    );

    protected $table = 'cybersource';

    protected $primaryKey = 'id';

    protected $entity = 'cybersource';

    public $incrementing = true;

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('Models\Payment\Entity', 'payment_id', 'id');
    }

    public function getStatus()
    {
        return $this->getAttribute('status');
    }

    public function setStatus($status)
    {
        $this->setAttribute('status', $status);
    }
}
