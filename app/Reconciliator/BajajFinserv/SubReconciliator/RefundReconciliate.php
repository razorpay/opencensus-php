<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_RRN                    = 'rrn';
    const COLUMN_ARN                    = 'utr_no';
    const COLUMN_PAYMENT_ID             = 'asset_serial_numberimei';
    const COLUMN_REFUND_AMOUNT          = 'amount_financed_rs';
    const COLUMN_TRANSACTION_DATE       = 'transaction_date';
    const COLUMN_GATEWAY_PAYMENT_ID     = 'transaction_id';

    protected function getRefundId($row)
    {
        $refundId = null;

        $paymentId = trim($row[self::COLUMN_PAYMENT_ID]);

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        if ($payment === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'info_code'     => Base\InfoCode::REFUND_PAYMENT_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            return $refundId;
        }

        $refundAmount = $this->getReconRefundAmount($row);

        $refunds = $this->repo->refund->findForPaymentAndAmount($paymentId, $refundAmount);

        if (count($refunds) === 1)
        {
            $refundId = $refunds[0]['id'];
        }else
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

    protected function getGatewaySettledAt(array $row)
    {
        $refundSettledAt = $row[self::COLUMN_TRANSACTION_DATE] ?? null;

        if (empty($refundSettledAt) === true)
        {
            return null;
        }

        $gatewaySettledAtTimestamp = null;

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($refundSettledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'date'      => $refundSettledAt,
                    'gateway'   => $this->gateway,
                    'batch_id'  => $this->batchId
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return trim($row[self::COLUMN_RRN] ?? null);
    }

    protected function getArn($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }
}
