<?php

namespace RZP\Reconciliator\Olamoney\SubReconciliator;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'unique_bill_id';
    const COLUMN_SERVICE_TAX        = ['service_tax', 'goods_and_services_tax'];
    const COLUMN_FEE                = 'tdr_deducted_in_rs';
    const COLUMN_SETTLED_AT         = 'date_of_settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';
    const COLUMN_PAYMENT_AMOUNT     = 'bill_amount_in_rs';

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
        $serviceTax = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[$serviceTaxColumn]);

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
        $fee = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);

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
            $this->trace->traceException(
                $ex,
                Logger::INFO,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'message'   => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'date'      => $columnSettledAt,
                    'gateway'   => $this->gateway,
                ]);
        }

        return $gatewaySettledAt;
    }
}
