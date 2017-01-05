<?php

namespace RZP\Reconciliator\Olamoney;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'Unique Bill Id';
    const COLUMN_REFUND_AMOUNT      = 'Bill Amount in Rs';
    const COLUMN_SETTLED_AT         = 'Date of Settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->wallet_olamoney->findSuccessfulRefundByRefundId($refundId);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
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

    protected function getGatewaySettledAt($row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(self::SETTLEMENT_DATE_FORMAT, $columnSettledAt, 'Asia/Kolkata');
            $gatewaySettledAt = $gatewaySettledAt->timestamp;
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            $this->app['trace']->traceException($ex);
        }

        return $gatewaySettledAt;
    }
}
