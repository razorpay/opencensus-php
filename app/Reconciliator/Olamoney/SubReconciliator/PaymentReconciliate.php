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
    const COLUMN_PAYMENT_ID         = 'Unique Bill Id';
    const COLUMN_SERVICE_TAX        = 'Service Tax';
    const COLUMN_FEE                = 'Tdr deducted in Rs';
    const COLUMN_SETTLED_AT         = 'Date of Settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

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
