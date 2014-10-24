<?php

namespace Gateway\Atom;

class Entity extends \Models\Base\Entity
{
    protected $fields = array(
        'id',
        'gateway_payment_id',
        'token',
        'success',
        'created_at',
        'updated_at');

    protected $fillable = array(
        'id',
        'gateway_payment_id',
        'token');

    protected $table = 'atom';

    protected $primaryKey = 'gateway_payment_id';

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('Payment', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }
}