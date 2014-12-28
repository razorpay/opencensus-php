<?php

namespace Gateway\Hdfc\Mpr;

use Models\Base;

class Entity extends Base\Entity
{
    protected $table = 'hdfc_mpr';

    protected $primaryKey = 'payment_id';

    protected $fillable = array(
        'payment_id',
        'gateway_payment_id',
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

    public function payment()
    {
        return $this->belongsTo('Models\Payment\Entity', 'trackid', 'id');
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }

    public function getAmount()
    {
        $ia = (int) $this->getAttribute('international_amount');
        $da = (int) $this->getAttribute('domestic_amount');
        return $ia + $da;
    }
}