<?php

namespace RZP\Reconciliator\Olamoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'unique_bill_id';
    const COLUMN_SERVICE_TAX        = ['service_tax', 'goods_and_services_tax'];
    const COLUMN_FEE                = 'tdr_deducted_in_rs';
    const COLUMN_SETTLED_AT         = 'date_of_settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = null;

        //
        // In new MIS files, we are getting GST with
        // column name Goods And Services Tax
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

        // Convert service tax into basic unit of currency (ex: paise)
        $serviceTax = Base\Helper::getIntegerFormattedAmount($row[$serviceTaxColumn]);

        return $serviceTax;
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

        // Convert fee into basic unit of currency (ex: paise)
        $fee = Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(self::SETTLEMENT_DATE_FORMAT, $columnSettledAt, Timezone::IST);
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
}
