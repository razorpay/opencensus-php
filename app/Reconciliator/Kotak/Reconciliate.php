<?php

namespace RZP\Reconciliator\Kotak;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = 'osrazorpay';

    const PAYMENT_COLUMN_HEADERS = [
        'merchant_id',
        'merchant_id2',
        'contact_no',
        'customer_name',
        'bank_id',
        'bank_id2',
        'amount',
        'date',
        'int_payment_id',
        'processed',
        'combined_details',
        'bank_reference_no',
        'date_time',
    ];

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        $typeName = null;

        if (strpos($fileName, self::SUCCESS) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }

        return $typeName;
    }

    public function getColumnHeadersForType($type)
    {
        $columnHeaders = [];

        if ($type === self::PAYMENT)
        {
            $columnHeaders = self::PAYMENT_COLUMN_HEADERS;
        }

        return $columnHeaders;
    }
}
