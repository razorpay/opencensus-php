<?php

namespace RZP\Reconciliator\Walnut369\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Walnut369\Reconciliate;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = Reconciliate::PURCHASE_OR_CANCELLED_AMOUNT;

    protected function getRefundId(array $row)
    {
        return $row[Reconciliate::RZP_TXN_ID] ?? null;
    }
}
