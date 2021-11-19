<?php

namespace RZP\Reconciliator\CardlessEmiEarlySalary;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const ORDER_ID                   = 'payment_id_order_id';
    const PAYMENT_AMOUNT             = 'settlement_amount';
    const BANK_CHARGES               = 'bank_charges';

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
