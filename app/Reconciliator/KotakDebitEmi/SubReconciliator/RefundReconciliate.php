<?php

namespace RZP\Reconciliator\KotakDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_REFUND_AMOUNT = ReconciliationFields::REFUND_AMOUNT;

    public function getRefundId(array $row)
    {
        $refundId = $row[ReconciliationFields::REFUND_CANCEL_ID] ?? null;

        return trim(str_replace("'", '', $refundId));
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[ReconciliationFields::ORDER_ID] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    public function getArn(array $row)
    {
        return $row[ReconciliationFields::ARN] ?? null;
    }
}
