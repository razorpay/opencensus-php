<?php

namespace Gateway\Hdfc\Mpr;

use Models\Base;

class Entity extends Base\Entity
{
    protected $table = 'hdfc_mpr';

    protected $primaryKey = 'track_id';

    protected $fillable = array(
        'track_id',
        'gateway_transaction_id',
        'gateway_merchant_id',
        'gateway_terminal_id',
        'card_trivia',
        'card_number',
        'card_type',
        'transaction_date',
        'settlement_date',
        'international_amount',
        'domestic_amount',
        'net_amount',
        'gateway_net_fee',
        'gateway_fee',
        'service_tax',
        'education_cess',
        'rec_format',
        'batch_number',
        'upvalue',
        'sequence_number',
        'approve_code',
    );

    public function getTrackId()
    {
        return $this->getAttribute('track_id');
    }

    public function getAmount()
    {
        $ia = (int) $this->getAttribute('international_amount');
        $da = (int) $this->getAttribute('domestic_amount');
        return $ia + $da;
    }
}