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
    const COLUMN_PAYMENT_ID      = 'order_id';
    const COLUMN_SERVICE_TAX     = ['service_tax', 'gstservice_tax'];
    const COLUMN_SB_CESS         = 'swachh_bharat_cess';
    const COLUMN_KK_CESS         = 'krishi_kalyan_cess';
    const COLUMN_FEE             = 'net_deduction';
    const COLUMN_PAYMENT_AMOUNT  = 'total_transaction_amount';
    const COLUMN_SETTLED_AT      = 'settlement_date';

    const SETTLEMENT_DATE_FORMAT = 'jS F Y';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = null;

        //
        // In new MIS files, we are getting GST with
        // column name GST/Service Tax
        //
        $serviceTaxColumn = array_first(self::COLUMN_SERVICE_TAX, function ($cst) use ($row)
        {
            return (isset($row[$cst]) === true);
        });

        if ($serviceTaxColumn === null)
        {
            $this->reportMissingColumn($row, self::COLUMN_SERVICE_TAX[0]);

            return null;
        }

        $serviceTax = $row[$serviceTaxColumn];

        // Convert service tax into basic unit of currency (ex: paise)
        $serviceTax = Base\Helper::getIntegerFormattedAmount($serviceTax);

        $sbCess = $this->getSbCess($row);
        $kkCess = $this->getKkCess($row);

        $serviceTax += $sbCess + $kkCess;

        return $serviceTax;
    }

    protected function getSbCess(array $row)
    {
        $sbCess = $row[self::COLUMN_SB_CESS] ?? null;

        return Base\Helper::getIntegerFormattedAmount($sbCess);
    }

    protected function getKkCess(array $row)
    {
        $kkCess = $row[self::COLUMN_KK_CESS] ?? null;

        return Base\Helper::getIntegerFormattedAmount($kkCess);
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
        return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);
    }

    protected function getReconPaymentAmount($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_AMOUNT]) === true)
        {
            return null;
        }

        return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
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
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}