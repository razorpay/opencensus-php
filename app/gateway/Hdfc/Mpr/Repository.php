<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;
use Models\Base;

class Mpr extends Base\Entity
{
    protected $table = 'hdfc_mpr';

    protected $primaryKey = 'id';

    protected $fillable = array(
        'transaction_id',
        'gateway_transaction_id',
        'gateway_merchant_id',
        'gateway_terminal_id',
        'card_network',
        'card_number',
        'card_type',
        'capture_date',
        'settlement_date',
        'international_amount',
        'domestic_amount',
        'net_amount',
        'gateway_net_fee',
        'gateway_fee',
        'service_tax',
        'education_cess',
        'reconciliation_format',
        'batch_number',
        'upvalue',
        'sequence_number',
        'approve_code',
    );

    public function transaction()
    {
        return $this->belongsTo('Models\Gateway\Transaction', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }

    public function getAmount()
    {
        return $this->getAttribute('international_amount') + $this->getAttribute('domestic_amount');
    }
}