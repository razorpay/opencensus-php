<?php

namespace RZP\Reconciliator\Axis;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID     = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_RRN            = 'rrn_no';
    const COLUMN_REFUND_AMOUNT  = 'txn_amount';

    /**
     * Axis reconciliation files only send us the rrn which is mapped
     * to api's refund id in axis migs gateway db.
     *
     * @param array $row
     * @return string Refund ID
     */
    protected function getRefundId(array $row)
    {
        $rrn = $row[self::COLUMN_RRN];

        if (empty($rrn) === true)
        {
            return null;
        }

        $axisMigsRepo = $this->app['repo']->axis_migs;

        $refundId = $axisMigsRepo->findByRrn($rrn)->getRefundId();

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                return $row[$cpi];
            }
        }

        return null;
    }

    protected function getRrn(array $row)
    {
        $rrn = $row[self::COLUMN_RRN];

        return $rrn;
    }
}
