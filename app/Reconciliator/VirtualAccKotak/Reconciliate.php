<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        'date',
        'txn_ref_no',
        'payee_account',
        'dealer_name',
        'master_account',
        'amount',
        'payee_name',
        'payer_name',
        'payer_account',
        'remitter_info',
        'payer_ifsc',
        'customer_code',
        'ref2',
        'mode',
        'time',
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
    protected function getTypeName($filename)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }
}
