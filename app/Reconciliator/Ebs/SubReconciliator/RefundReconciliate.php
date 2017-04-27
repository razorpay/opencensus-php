<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    // ----- Row header names -----
    const COLUMN_REFUND_ID          = 'merchant_ref_no';

    const COLUMN_REFUND_AMOUNT      = 'refunded';
    const COLUMN_DEBIT_AMOUNT       = 'debit';

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
        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === true)
        {
            $refundColumnVal = $row[self::COLUMN_REFUND_AMOUNT];
        }

        elseif (isset($row[self::COLUMN_DEBIT_AMOUNT]) === true)
        {
            $refundColumnVal = $row[self::COLUMN_DEBIT_AMOUNT];
        }

        $paymentAmount = floatval($refundColumnVal) * 100;

        return abs($paymentAmount);
    }
}
