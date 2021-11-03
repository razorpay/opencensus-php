<?php

namespace RZP\Reconciliator\PaylaterLazypay\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\PaylaterLazypay\Reconciliate;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = Reconciliate::TRANSACTION_AMOUNT;

    protected function getRefundId(array $row)
    {
        return $row[Reconciliate::PG_TXN_NO] ?? null;
    }
}
