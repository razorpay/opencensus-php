<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;


class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'merchant_trackid';

    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];
        $refundId = trim(str_replace("'", '', $refundId));

        return $refundId;
    }
}