<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'Ref. 1';
    const COLUMN_SERVICE_TAX        = 'S Tax (Rs Ps)';
    const COLUMN_FEE                = 'Charges (Rs.Ps)';
    const COLUMN_GST                = 'GST (Rs Ps)';
    const COLUMN_SETTLED_AT         = 'Settlement Date';
    // 29/06/2017 00:31:08
    const SETTLEMENT_DATE_FORMAT    = 'd/m/Y H:i:s';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = null;

        //
        // Post July 1st 2017, we get only GST and no service tax.
        //
        if (isset($row[self::COLUMN_SERVICE_TAX]) === true)
        {
            $serviceTax = $row[self::COLUMN_SERVICE_TAX];
        }

        // Convert service tax into basic unit of currency (ex: paise)
        $serviceTax = floatval($serviceTax) * 100;

        $gst = $this->getGst($row);

        $serviceTax += $gst;

        return round($serviceTax);
    }

    protected function getGst(array $row)
    {
        $columnGst = null;

        if (isset($row[self::COLUMN_GST]) === true)
        {
            $columnGst = $row[self::COLUMN_GST];
        }

        $gst = floatval($columnGst) * 100;

        return $gst;
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // BillDesk reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
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

            $this->trace->traceException($ex);
        }

        return $gatewaySettledAt;
    }
}
