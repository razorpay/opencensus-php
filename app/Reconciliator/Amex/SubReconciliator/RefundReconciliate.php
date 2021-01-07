<?php

namespace RZP\Reconciliator\Amex\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_AMOUNT              = 'charge_amount';
    const COLUMN_SETTLED_AT_DATE            = 'settlement_date';
    const COLUMN_CHARGE_REFERENCE_NUMBER    = 'charge_reference_number';

    protected function getRefundId($row)
    {
        $refundId = null;

        $paymentId = trim($row[self::COLUMN_CHARGE_REFERENCE_NUMBER] ?? null);

        $refundAmount = $this->getReconRefundAmount($row);

        try
        {
            $payment = $this->repo->payment->findOrFailPublic($paymentId);
        }
        catch (\Throwable $e)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'info_code'     => Base\InfoCode::REFUND_PAYMENT_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId
                ]);

            return null;
        }

        $convertCurrency = $payment->getConvertCurrency();

        if ($convertCurrency === true)
        {
            $refunds = $this->repo->refund->findForPaymentAndBaseAmount($paymentId, $refundAmount);
        }
        else
        {
            $refunds = $this->repo->refund->findForPaymentAndAmount($paymentId, $refundAmount);
        }

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

    /**
     * For majority of the cases, we get payment
     * id in charge_reference_number column, in such
     * cases, we need not save gateway transaction id
     * as it could be misleading. So, only saving in
     * case when it's not a unique id.
     *
     * @param array $row
     * @return mixed|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function getGatewayTransactionId(array $row)
    {
        $isValidPaymentId = Entity::verifyUniqueId(($row[self::COLUMN_CHARGE_REFERENCE_NUMBER] ?? null), false);

        return $isValidPaymentId ? null : $row[self::COLUMN_CHARGE_REFERENCE_NUMBER];
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT_DATE]) === true)
        {
            return null;
        }

        $gatewaySettledAt = null;

        $settledAt = $row[self::COLUMN_SETTLED_AT_DATE];

        if (strpos($settledAt, '-') !== false)
        {
            $settledAt = str_replace('-', '/', $settledAt);
        }

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat('d/m/Y', $settledAt, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'        => $settledAt,
                    'refund_id'         => $this->refund->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $gatewaySettledAt;
    }
}
