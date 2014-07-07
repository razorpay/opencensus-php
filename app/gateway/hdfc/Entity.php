<?php

namespace Gateway\Hdfc;

class Entity extends \Models\Base\Entity
{
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