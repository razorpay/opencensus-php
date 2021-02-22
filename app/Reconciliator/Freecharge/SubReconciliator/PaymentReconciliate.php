<?php

namespace RZP\Reconciliator\Freecharge\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID      = 'order_id';
    const COLUMN_SERVICE_TAX     = ['service_tax', 'gstservice_tax'];
    const COLUMN_FEE             = 'net_deduction';
    const COLUMN_PAYMENT_AMOUNT  = 'total_transaction_amount';
    const COLUMN_SETTLED_AT      = 'settlement_date';
    const COLUMN_IGST            = 'igst';

    const SETTLEMENT_DATE_FORMAT = 'jS F Y';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $igstFee = $row[self::COLUMN_IGST] ?? null;

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($igstFee);
    }

    protected function getGatewayFee($row)
    {
        //
        // This should be isset not empty as fee can be 0 also.
        //
        if (isset($row[self::COLUMN_FEE]) === false)
        {
            $this->reportMissingColumn($row, self::COLUMN_FEE);

            return null;
        }

        //
        // The fee is provided as a separate column.
        // But we use the column which is the net deduction.
        // Hence, we don't need to add the service tax to this.
        //
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_AMOUNT]) === true)
        {
            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(
                                    self::SETTLEMENT_DATE_FORMAT,
                                    $row[self::COLUMN_SETTLED_AT],
                                    Timezone::IST);

            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'payment_id'    => $this->payment->getId(),
                    'date'          => $row[self::COLUMN_SETTLED_AT],
                    'gateway'       => $this->gateway
                ]);

            $this->app['trace']->traceException($ex);
        }

        return $gatewaySettledAt;
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
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
