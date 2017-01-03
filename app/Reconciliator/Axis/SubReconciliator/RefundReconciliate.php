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

        $axisMigsRepo = $this->app['repo']->axis_migs;

        $refundId = $axisMigsRepo->findByRrn($rrn)->getRefundId();

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (isset($row[$cpi]) === true)
            {
                return $row[$cpi];
            }
        }

        return null;
    }

    protected function getRefundAmount(array $row)
    {
        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = floatval($row[self::COLUMN_REFUND_AMOUNT]) * 100;

        return $refundAmount;
    }

    protected function createRefundOnApi(array $row, string $refundId, \Exception $ex)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_INFO_ALERT,
                'message'    => 'Refund not found in DB. -> ' . $ex->getMessage(),
                'row'        => $row,
                'refund_id'  => $refundId,
                'gateway'    => get_called_class()
            ]);

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getRefundAmount($row);

        if (($paymentId === null) or ($refundAmount === null))
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'row' => $row,
                    'message' => 'Unable to get the payment ID or amount from the refund recon file',
                    'refund_id' => $refundId
                ]);

            return false;
        }

        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $processor = new Payment\Processor\Processor($merchant);

        try
        {
            $processor->createRefundOnApiFromRecon($payment, $refundId, $refundAmount);
        }
        catch (\Exception $ex)
        {
            $this->app['trace']->traceException($ex);

            return false;
        }

        return true;
    }
}
