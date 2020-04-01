<?php

namespace RZP\Reconciliator\HdfcDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT = ReconciliationFields::AMOUNT;

    public function getRefundId(array $row)
    {
        $paymentId = $row[ReconciliationFields::MERCHANT_REFERENCE_NUMBER] ?? null;

        $paymentId = trim(str_replace("'", '', $paymentId));

        // This gateway does not support partial refund and hence can have only max 1 refund per payment
        $refund = $this->repo->refund->fetchFirstForPaymentId($paymentId);

        return $refund ? $refund->getId() : $refund;
    }
}
