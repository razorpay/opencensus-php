<?php

namespace RZP\Reconciliator\Cred\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    protected function getRefundId(array $row)
    {
        return $row[ReconciliationFields::P1_TRANSACTION_ID] ?? null;
    }
}
