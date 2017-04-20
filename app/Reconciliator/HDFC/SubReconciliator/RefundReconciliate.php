<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID      = 'merchant_trackid';
    const COLUMN_REFUND_AMOUNT  = 'domestic_amt';
    const COLUMN_RRN            = '';

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];
        $refundId = trim(str_replace("'", '', $refundId));

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->hdfc->findSuccessfulRefundByRefundId($refundId);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
    }

    protected function getRRN(array $row)
    {
        $rrn = $row[self::COLUMN_RRN];

        return $rrn;
    }
}
