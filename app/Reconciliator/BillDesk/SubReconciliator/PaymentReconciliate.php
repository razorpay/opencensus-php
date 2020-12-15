<?php

namespace RZP\Reconciliator\BillDesk\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'ref_1';
    const COLUMN_SERVICE_TAX        = 's_tax_rs_ps';
    const COLUMN_FEE                = 'charges_rsps';
    const COLUMN_GST                = 'gst_rs_ps';
    const COLUMN_SETTLED_AT         = 'settlement_date';
    const COLUMN_PAYMENT_AMOUNT     = 'gross_amountrsps';

    // 29/06/2017 00:31:08
    const SETTLEMENT_DATE_FORMAT    = 'd/m/Y H:i:s';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? null;
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
                    'date'          => $row[self::COLUMN_SETTLED_AT],
                    'payment_id'    => $this->payment->getId(),
                    'gateway'       => $this->gateway
                ]);

            $this->trace->traceException($ex);
        }

        return $gatewaySettledAt;
    }
}
