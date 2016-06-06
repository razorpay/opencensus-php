<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;


class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID = 'merchant_trans_ref';
    const RRN = 'rrn_no';


    protected function getRefundId($row)
    {
        $rrn = $row[self::RRN];

        $axisMigsRepo = $this->app['repo']->axis_migs;

        $refundId = $axisMigsRepo->findByRrn($rrn)->getRefundId();

        return $refundId;
    }
}