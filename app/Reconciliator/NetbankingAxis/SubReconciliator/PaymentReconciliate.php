<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN No';
    const COLUMN_BANK_PAYMENT_ID = 'BID';

    protected function getPaymentId($row)
    {
        return $row[self::COLUMN_PAYMENT_REF_NO];
    }

    protected function getBankPaymentId($row)
    {
        return $row[self::COLUMN_BANK_PAYMENT_ID];
    }
}
