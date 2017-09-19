<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        'txn_date',
        'txn_ref_no',
        'e_coll_ac_no',
        'dealer_name',
        'master_ac_no',
        'amount',
        'bene_cust_acname',
        'send_cust_acname',
        'send_cust_ac_no',
        'remitt_info',
        'snd_brn_ifsc',
        'customer_code',
        'ref2',
        'ref3',
        'credit_time',
    ];

    /**
     * Kotak Virtual Account MIS files contain only payment info.
     *
     * Refunds are handled via payouts made from nodal directly
     * to customer bank account, and not via Kotak E-Collect
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName()
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }
}
