<?php

namespace Gateway\MockHdfc;

use Models\Base\Entity;

class MprGenerator extends Entity
{
    protected $table = 'mockhdfc_mpr_generator';

    protected $primaryKey = 'merchant_trackid';

    protected $fillable = array(
        'merchant_code',
        'terminal_number',
        'rfc_fmt',
        'bat_nbr',
        'card_type',
        'card_number',
        'trans_date',
        'settle_date',
        'approv_code',
        'intl_amt',
        'domestic_amt',
        'tran_id',
        'upvalue',
        'merchant_trackid',
        'msf',
        'service_tax',
        'edu_cess',
        'net_amount',
        'debitcredit_type',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5',
        'sequence_number',
        'mpr_generated',
    );

    protected $hidden = array();

    public function getMerchantTrackidAttribute($value)
    {
        return 'txn-' . $value;
    }

    public function toArrayForMprReport()
    {
        $array = $this->toArray();

        unset($array['created_at']);
        unset($array['updated_at']);
        unset($array['mpr_generated']);

        return $array;
    }

}