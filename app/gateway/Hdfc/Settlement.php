<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;
use Models\Base;

class Mpr extends Base\Entity
{
    protected $table = 'hdfc_mpr';

    protected $primaryKey = 'id';

    protected $fillable = array(
        'merchant code',
        'terminal number',
        'rec fmt',
        'bat nbr',
        'card type',
        'card number',
        'trans date',
        'settle date',
        'approv code',
        'intnl amt',
        'domestic amt',
        'tran_id',
        'upvalue',
        'merchant_trackid',
        'msf',
        'service tax',
        'edu cess',
        'net amount',
        'debitcredit_type',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5',
        'sequence number');

    public function transaction()
    {
        return $this->belongsTo('Models\Gateway\Transaction', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }
}