<?php

namespace RZP\Reconciliator\CardlessEmiZestMoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const TRANSACTION_TYPE           = 'transactiontype';
    const ORDER_ID                   = 'orderid';
    const PAYMENT_AMOUNT             = 'basketamount';
    const REFUND_AMOUNT              = 'refundamount';
    const BANK_CHARGES               = 'mdramount';
    const GST_ON_BANK_CHARGES        = 'mdrgstamount';
    const REFUND_ID                  = 'refundid';

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
