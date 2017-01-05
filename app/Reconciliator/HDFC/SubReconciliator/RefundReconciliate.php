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
    const COLUMN_REFUND_ID = 'merchant_trackid';
    const COLUMN_REFUND_AMOUNT = 'domestic_amt';

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
