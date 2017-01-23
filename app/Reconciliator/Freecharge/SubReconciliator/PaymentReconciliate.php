<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use RZP\Exception\ReconciliationException;
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
    const COLUMN_SETTLED_AT      = 'Transaction Date';

    const SETTLEMENT_DATE_FORMAT = 'd/m/Y H:i:s T';

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

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        return round($fee);
    }

    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval($paymentAmount);
    }

    protected function getGatewaySettledAt($row)
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
                                    'Asia/Kolkata');
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

    protected function assertPaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getAmount() !== $this->getGatewayPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Payment amount mismatch',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);
        }
    }
}
