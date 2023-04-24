<?php

namespace RZP\Reconciliator\Mobikwik\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID    = 'orderid';
    const COLUMN_REFUND_AMOUNT = 'refundamount';
    const COLUMN_REFUND_ID     = 'refundid';

    /**
     * Gets refund Id from row data
     *
     * Since only paymentId is provided, we query the
     * refunds table, to check if a unique entity is
     * present with given payment id and amount. Only
     * if we get a unique entity, we proceed with recon.
     *
     */
    protected function getRefundId($row)
    {
        $refundId = null;

        /**
         *
         * Will get refundid in Recon file after making changes for partial refund for the same amount
         *
         */
        if (isset($row[self::COLUMN_REFUND_ID]) === true)
        {
            $refundId = $row[self::COLUMN_REFUND_ID];

            return $refundId;
        }
        else
        {
            $paymentId = $this->getPaymentId($row);

            if (empty($paymentId) === true)
            {
                return null;
            }

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
    }

    /**
     * In mobikwik, we do not get a refund id.
     * The orderId column provided is a paymentId
     *
     * We use that to get the corresponding refund id
     *
     * @param array $row
     * @return mixed
     */
    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];

            $paymentId = trim(str_replace('"', '', $paymentId));
        }

        return $paymentId;
    }
}
