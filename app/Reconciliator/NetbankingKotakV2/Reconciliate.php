<?php

namespace RZP\Reconciliator\NetbankingKotakV2;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const ENTITY_CODE             = 'Entity Code';
    const MERCHANT_CODE           = 'Merchant Code';
    const MCC_CODE                = 'MCC Code';
    const PARTY_NAME              = 'Party Name';
    const PARTY_ID                = 'Party CRN';
    const FROMAPAC                = 'FROM APAC';
    const AMOUNT                  = 'Transaction Amount';
    const CHARGES                 = 'Charges';
    const GST                     = 'GST';
    const REQUEST_DATE            = 'Request Date';
    const ENTITY_REFERENCE_NUMBER = 'Entity Reference No';
    const BANK_REFERENCE_NUMBER   = 'Bank Reference No';
    const DATE                    = 'Payment Date';
    const PROCESS_DATE            = 'fc_process_date';


    public static $columnHeaders = [
        self::ENTITY_CODE,
        self::MERCHANT_CODE,
        self::MCC_CODE,
        self::PARTY_NAME,
        self::PARTY_ID,
        self::FROMAPAC,
        self::AMOUNT,
        self::CHARGES,
        self::GST,
        self::REQUEST_DATE,
        self::ENTITY_REFERENCE_NUMBER,
        self::BANK_REFERENCE_NUMBER,
        self::DATE,
        self::PROCESS_DATE,
    ];

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
        return ',';
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
