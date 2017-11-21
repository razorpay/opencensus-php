<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID      = 'Order Id';
    const COLUMN_SERVICE_TAX     = 'Service Tax';
    const COLUMN_SB_CESS         = 'Swachh Bharat Cess';
    const COLUMN_KK_CESS         = 'Krishi Kalyan Cess';
    const COLUMN_FEE             = 'Net Deduction';
    const COLUMN_PAYMENT_AMOUNT  = 'Total Transaction Amount';
    const COLUMN_SETTLED_AT      = 'Settlement Date';

    const SETTLEMENT_DATE_FORMAT = 'jS F Y';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        if (empty($row[self::COLUMN_SB_CESS]) === false)
        {
            $sbCess = floatval($row[self::COLUMN_SB_CESS]) * 100;

            $serviceTax += $sbCess;
        }

        if (empty($row[self::COLUMN_KK_CESS]) === false)
        {
            $kkCess = floatval($row[self::COLUMN_KK_CESS]) * 100;

            $serviceTax += $kkCess;
        }

        return intval(round($serviceTax));
    }

    protected function getGatewayFee($row)
    {
        //
        // The fee is provided as a separate column.
        // But we use the column which is the net deduction.
        // Hence, we don't need to add the service tax to this.
        //
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        return intval($fee);
    }

    protected function getReconPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        //
        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946
        // Convering to string using number_format and then converting
        // is a hack to avoid this issue
        //
        return intval(number_format($paymentAmount, 2, '.', ''));
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
                    'row'           => $row,
                    'gateway'       => get_called_class()
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
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
