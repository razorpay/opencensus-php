<?php

namespace RZP\Reconciliator\VirtualAccYesBank;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        'cust_code',
        'remitter_code',
        'customer_subcode',
        'invoice_no',
        'bene_account_no',
        'amount',
        'rmtr_account_no',
        'rmtr_account_ifsc',
        'transaction_ref_no',
        'trans_received_at',
        'trans_status',
        'validation_status',
        'transfer_type',
        'credit_ref',
        'notify_status',
        'notify_result',
        'return_ref',
        'returned_at',
        'rmtr_full_name',
        'rmtr_add',
        'udf11',
        'udf12',
        'udf13',
        'udf14',
    ];

    /**
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
