<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO = 'PRN No';

    protected function getPaymentId($row)
    {
        return $row[self::COLUMN_PAYMENT_REF_NO];
    }
}
