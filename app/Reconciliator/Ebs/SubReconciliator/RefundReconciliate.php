<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    // ----- Row header names -----
    const COLUMN_REFUND_ID          = 'merchant_ref_no';
    const COLUMN_REFUND_AMOUNT      = 'refunded';

    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function getPaymentId($row)
    {
        $paymentId = $this->getRefundId($row);

        return $paymentId;
    }

    protected function getRefundAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_REFUND_AMOUNT]) * 100;

        return abs($paymentAmount);
    }
}
