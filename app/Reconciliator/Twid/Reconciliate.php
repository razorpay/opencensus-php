<?php

namespace RZP\Reconciliator\Twid;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const SR_NO                      = 'sr_no';
    const TRANSACTION_ID             = 'Transaction ID';
    const MERCHANT_TRANSACTION_ID    = 'Merchant Transaction ID';
    const DATE                       = 'Date';
    const BRAND                      = 'Brand';
    const BILL_VALUE                 = 'Bill Value';
    const COMMISSION                 = 'Commission';
    const GST_ON_COMMISSION          = 'GST on Commission';
    const TOTAL_COMMISSION           = 'Total Commission';
    const TOTAL_PAYABLE              = 'Total Payable';
    const STATUS                     = 'Status';
    const TWID_REFUND_ID             = 'Twid Refund Id';
    const MERCHANT_REFUND_ID         = 'Merchant Refund Id';
    const REFUND_DATE                = 'Refund Date';


    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getDelimiter()
    {
        return ',';
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP => 0,
            FileProcessor::LINES_FROM_BOTTOM => 2
        ];
    }
}
