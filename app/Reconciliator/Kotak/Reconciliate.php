<?php

namespace RZP\Reconciliator\Kotak;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = ['osrazorpay', 'otrazorpay'];

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

    const REFUND_COLUMN_HEADERS = [
        'Count',
        'FILE NAME',
        'FILE RECEIVED DATE',
        'MERCHANT ID',
        'MERCHANT REF NO',
        'FROM APAC',
        'TO APAC',
        'PROCESSED FLAG',
        'AMOUNT',
        'PROCESSED DATE',
        'PROC REMARKS',
        'AUTHORIZED BY',
        'AUTHORIZED DATE',
        'BANK REF NO',
        'ACTUAL TXN AMOUNT',
        'REFUND MERCHANT REF NO',
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

        foreach (self::SUCCESS as $name)
        {
            if (strpos($fileName, $name) !== false)
            {
                $typeName = self::PAYMENT;
            }
        }

        if (strpos($fileName, self::REFUND) !== false)
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

        else if ($type === self::REFUND)
        {
            $columnHeaders = self::REFUND_COLUMN_HEADERS;
        }

        return $columnHeaders;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        $type = $this->getTypeName($fileDetails['file_name']);

        if ($type === self::REFUND)
        {
            return [
                FileProcessor::LINES_FROM_TOP       => 1,
                FileProcessor::LINES_FROM_BOTTOM    => 0
            ];
        }
        else
        {
            return parent::getNumLinesToSkip($fileDetails);
        }
    }
}
