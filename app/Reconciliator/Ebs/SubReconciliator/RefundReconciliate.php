<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    // ----- Row header names -----
    const COLUMN_REFUND_ID          = 'merchant_ref_no';
    const COLUMN_REFUND_AMOUNT      = 'refunded';
    const COLUMN_GATEWAY_PAYMENT_ID = 'paymentid';

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }
}
