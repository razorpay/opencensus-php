<?php

namespace RZP\Reconciliator\Paytm\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID             = 'order_id';
    const COLUMN_SERVICE_TAX            = 's_tax';
    const COLUMN_FEE                    = 'rev_commm';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        // This is a hack and is being done only for PayTm because there seem
        // to be payments in the recon which are not there in our DB.
        // This is probably because these were done on local.
        $paymentExists = $this->checkPaymentExists($paymentId);

        if ($paymentExists === false)
        {
            return null;
        }

        return $paymentId;
    }

    protected function checkPaymentExists($paymentId)
    {
        $payment = $this->paymentRepo->find($paymentId);

        if ($payment === null)
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'  => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into basic unit of currency. (ex: paise)
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
}
