<?php

namespace RZP\Reconciliator\Amex\SubReconciliator;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Amex\Entity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_GATEWAY_PAYMENT_ID     = 'reference_number';
    const COLUMN_AMOUNT                 = 'charge_amount';
    const COLUMN_SETTLED_AT_DATE        = 'settlement_date';

    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (isset($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === true)
        {
            $paymentId = $this->repo->amex
                ->findPaymentForGateway($row[self::COLUMN_GATEWAY_PAYMENT_ID])
                ->getPaymentId();
        }

        return $paymentId;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'actual_amount'   => $this->getReconPaymentAmount($row),
                    'row'             => $row,
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT] ?? null);
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT_DATE]) === true)
        {
            return null;
        }

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat('d/m/Y', $row[self::COLUMN_SETTLED_AT_DATE],
                                                      Timezone::IST);

            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get Gateway Settled at',
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);
        }

        return $gatewaySettledAt;
    }
}
