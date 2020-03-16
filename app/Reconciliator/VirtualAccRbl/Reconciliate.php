<?php

namespace RZP\Reconciliator\VirtualAccRbl;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        'transaction_type',
        'amount',
        'utr_number',
        'rrn_number',
        'sender_ifsc',
        'sender_account_number',
        'sender_account_type',
        'sender_name',
        'beneficiary_account_type',
        'beneficiary_account_number',
        'benename',
        'credit_date',
        'credit_account_number',
        'corporate_code',
        'sender_information',
    ];

    /**
     *
     * @param $filename
     * @return string
     */
    protected function getTypeName($filename)
    {
        return self::PAYMENT;
    }

    /**
     * This header will be used when we get .txt or csv file without header.
     * For excel files, this is not used.
     *
     * @param $type
     * @return array
     */
    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }
}
