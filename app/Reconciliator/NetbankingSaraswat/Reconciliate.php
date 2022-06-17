<?php

namespace RZP\Reconciliator\NetbankingSaraswat;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_ID            = 'Paymentid';
    const BANK_REFERENCE_NUMBER = 'Bid';
    const AMT                   = 'Amount';
    const TXNDATE               = 'Date';
    const REFUND_AND_NARRATION  = 'Refund_and_narration';


    public static $columnHeaders = [
        self::PAYMENT_ID,
        self::BANK_REFERENCE_NUMBER,
        self::AMT,
        self::REFUND_AND_NARRATION,
        self::TXNDATE,
    ];

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getColumnHeadersForType($type)
    {
        return self::$columnHeaders;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return '^';
    }
}
