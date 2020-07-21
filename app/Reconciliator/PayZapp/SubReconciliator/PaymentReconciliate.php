<?php

namespace RZP\Reconciliator\PayZapp\SubReconciliator;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_GATEWAY_PAYMENT_ID2 = 'pg_sale_id';
    const COLUMN_PAYMENT_ID          = 'track_id';
    const COLUMN_SERVICE_TAX         = ['cgst', 'igst', 'sgst', 'utgst'];
    const COLUMN_FEE                 = 'commission_amt';
    const COLUMN_AMOUNT              = 'gross_amt';
    const COLUMN_PAYMENT_DATE        = 'tran_date';
    const GATEWAY_PAYMENT_DATE_FORMAT= 'Y-m-d H:i:s.u';


    protected function getPaymentId(array $row)
    {
        if (isset($row[self::COLUMN_GATEWAY_PAYMENT_ID2]) === false)
        {
            return null;
        }

        $gatewayPaymentId2 = $row[self::COLUMN_GATEWAY_PAYMENT_ID2];

        $payzappRepo = $this->app['repo']->wallet;

        $gatewayPayment = $payzappRepo->fetchWalletByGatewayPaymentId2($gatewayPaymentId2);

        if ($gatewayPayment === null)
        {
            if ((isset($row[self::COLUMN_PAYMENT_ID]) === true) and
                (PublicEntity::verifyUniqueId($row[self::COLUMN_PAYMENT_ID], false) === true))
            {
                return $row[self::COLUMN_PAYMENT_ID];
            }

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'            => TraceCode::RECON_INFO_ALERT,
                    'info_code'             => Base\InfoCode::PAYMENT_ABSENT ,
                    'payment_reference_id'  => $gatewayPaymentId2,
                    'payment_id'            => $row[self::COLUMN_PAYMENT_ID] ?? null,
                    'gateway'               => $this->gateway,
                ]
            );

            return null;
        }

        return $gatewayPayment->getPaymentId();
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax and GST into paise
        $serviceTax = 0;

        foreach (self::COLUMN_SERVICE_TAX as $tax)
        {
            if (isset($row[$tax]) === false)
            {
                $this->reportMissingColumn($row, $tax);

                return null;
            }

            $serviceTax += Helper::getIntegerFormattedAmount($row[$tax]);
        }

        return $serviceTax;
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = 0;

        if (isset($row[self::COLUMN_FEE]) === false)
        {
            $this->reportMissingColumn($row, self::COLUMN_FEE);
        }

        $fee += Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        if ($serviceTax === null)
        {
            return null;
        }

        // PayZapp reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
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
                    'gateway'         => $this->payment->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[self::COLUMN_AMOUNT]) === false)
        {
            return 0;
        }
        return Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
    }

    public function getGatewayPayment($paymentId)
    {
        $gatewayPayment = $this->repo->wallet->fetchWalletByPaymentId($paymentId);

        return $gatewayPayment;
    }

    protected function getGatewayPaymentDate($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_DATE]) === true)
        {
            return null;
        }

        $gatewayPaymentDate = null;

        try
        {
            $gatewayPaymentDate = Carbon::createFromFormat(
                self::GATEWAY_PAYMENT_DATE_FORMAT,
                $row[self::COLUMN_PAYMENT_DATE],
                Timezone::IST);
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'       => 'Unable to parse gateway payment date',
                    'payment_id'    => $this->getPaymentId($row),
                    'date'          => $row[self::COLUMN_PAYMENT_DATE],
                    'gateway'       => $this->gateway,
                ]);

            $this->app['trace']->traceException($ex);
        }

        return $gatewayPaymentDate;
    }
}
