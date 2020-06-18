<?php

namespace RZP\Reconciliator\Ebs\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = ['merchant_ref_no', 'merchant_refno'];
    const COLUMN_REFUND_AMOUNT      = 'debit';

    protected function getRefundId($row)
    {
        $refundId = null;

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getReconRefundAmount($row);

        $refunds = $this->repo->refund->findForPaymentAndAmount($paymentId, $refundAmount);

        if (count($refunds) === 1)
        {
            $refundId = $refunds[0]['id'];
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::RECON_UNIQUE_REFUND_NOT_FOUND,
                    'payment_id'            => $paymentId,
                    'refund_amount'         => $refundAmount,
                    'refund_count'          => count($refunds),
                    'gateway'               => $this->gateway,
                    'batch_id'              => $this->batchId,
                ]);
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentIdCol = array_first(self::COLUMN_REFUND_ID,
            function ($col) use ($row)
            {
                return (isset($row[$col]) === true);
            });

        return $row[$paymentIdCol];
    }
}
