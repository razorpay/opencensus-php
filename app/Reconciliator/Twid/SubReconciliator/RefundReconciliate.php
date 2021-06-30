<?php

namespace RZP\Reconciliator\Twid\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Twid\Reconciliate;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = Reconciliate::BILL_VALUE;

    protected function getRefundId(array $row)
    {
        return $row[Reconciliate::MERCHANT_REFUND_ID] ?? null;
    }
}
