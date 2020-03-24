<?php

namespace RZP\Reconciliator\HdfcDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = ReconciliationFields::AMOUNT;

    public function getRefundId(array $row)
    {
        $refundId = $row[ReconciliationFields::MERCHANT_REFERENCE_NUMBER] ?? null;

        return trim(str_replace("'", '', $refundId));
    }
}
