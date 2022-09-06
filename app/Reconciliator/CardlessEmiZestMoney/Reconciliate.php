<?php

namespace RZP\Reconciliator\CardlessEmiZestMoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const TRANSACTION_TYPE           = 'transaction_type';
    const ORDER_ID                   = 'partner_order_id';
    const PAYMENT_AMOUNT             = 'basket_amount';
    const REFUND_AMOUNT              = 'refund_amount';
    const BANK_CHARGES               = 'mdr_wo_gst_amount';
    const GST_ON_BANK_CHARGES        = 'mdr_gst_amount';
    const REFUND_ID                  = 'refund_id';

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
