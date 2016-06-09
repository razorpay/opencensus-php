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
    
    /**
     * Axis reconciliation files only send us the rrn which is mapped
     * to api's refund id in axis migs gateway db.
     * 
     * @param array $row
     * @return string Refund ID
     */
    protected function getRefundId(array $row)
    {
        $rrn = $row[self::RRN];

        $axisMigsRepo = $this->app['repo']->axis_migs;

        $refundId = $axisMigsRepo->findByRrn($rrn)->getRefundId();

        return $refundId;
    }
}